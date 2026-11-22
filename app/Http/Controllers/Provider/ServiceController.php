<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveServiceRequest;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        return view('provider.services.index', [
            'services' => $request->user()->services()->whereNull('archived_at')->with('category')->latest('id')->paginate(12),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Service::class);

        return view('provider.services.form', [
            'service' => new Service,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(SaveServiceRequest $request): RedirectResponse
    {
        $service = $request->user()->services()->make();
        $this->saveService($request, $service);

        return to_route('provider.dashboard')->with('status', 'Service created as a draft.');
    }

    public function edit(Service $service): View
    {
        Gate::authorize('update', $service);

        return view('provider.services.form', [
            'service' => $service,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(SaveServiceRequest $request, Service $service): RedirectResponse
    {
        $this->saveService($request, $service);

        return to_route('provider.dashboard')->with('status', 'Service updated.');
    }

    public function updatePublication(Request $request, Service $service): RedirectResponse
    {
        Gate::authorize('update', $service);
        $request->validate(['is_published' => ['required', 'boolean']]);
        $published = $request->boolean('is_published');

        if ($published && ($service->duration_minutes === null || $service->location === null)) {
            return to_route('provider.services.edit', $service)->withErrors([
                'duration_minutes' => 'Add a duration and location before publishing.',
            ]);
        }

        $service->newQuery()->whereKey($service->id)->whereNull('archived_at')->update(['is_published' => $published]);

        return to_route('provider.dashboard')->with('status', $published ? 'Service published.' : 'Service deactivated.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        Gate::authorize('delete', $service);

        $updated = $service->newQuery()
            ->whereKey($service->id)
            ->whereNull('archived_at')
            ->where('image_path', $service->image_path)
            ->update(['is_published' => false, 'archived_at' => now(), 'image_path' => null]);

        abort_unless($updated, 409, 'The service changed. Refresh and try again.');

        $this->deleteImage($service->image_path);

        return to_route('provider.dashboard')->with('status', 'Service deleted from your listings.');
    }

    private function saveService(SaveServiceRequest $request, Service $service): void
    {
        $service->fill($request->safe()->only(['title', 'description', 'duration_minutes', 'location']));
        $service->category()->associate($request->validated('category_id'));
        $service->price_cents = $request->priceInCents();
        $previousImage = $service->image_path;
        $newImage = null;
        $removeImage = $request->boolean('remove_image');

        if ($request->hasFile('image')) {
            $newImage = $request->file('image')->store('services', 'public');

            if ($newImage === false) {
                throw ValidationException::withMessages(['image' => 'The image could not be saved. Please try again.']);
            }
        }

        if ($newImage !== null || $removeImage) {
            $service->image_path = $newImage;
        }

        try {
            if ($service->exists) {
                $changes = $service->getDirty();
                if ($changes !== []) {
                    $query = $service->newQuery()->whereKey($service->id)->whereNull('archived_at');

                    if ($newImage !== null || $removeImage) {
                        $query->where('image_path', $previousImage);
                    }

                    abort_unless($query->update($changes), 409, 'The service changed. Refresh and try again.');
                }
            } else {
                $service->save();
            }
        } catch (Throwable $exception) {
            $this->deleteImage($newImage);

            throw $exception;
        }

        if ($previousImage !== $service->image_path) {
            $this->deleteImage($previousImage);
        }
    }

    private function deleteImage(?string $path): void
    {
        if ($path === null || ! str_starts_with($path, 'services/')) {
            return;
        }

        try {
            if (! Storage::disk('public')->delete($path)) {
                report(new RuntimeException('Could not delete a service image from the public disk.'));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
