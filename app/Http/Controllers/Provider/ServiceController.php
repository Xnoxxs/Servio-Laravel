<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveServiceRequest;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

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

        $service->forceFill(['is_published' => false, 'archived_at' => now()])->save();

        return to_route('provider.dashboard')->with('status', 'Service deleted from your listings.');
    }

    private function saveService(SaveServiceRequest $request, Service $service): void
    {
        $service->fill($request->safe()->only(['title', 'description', 'duration_minutes', 'location']));
        $service->category()->associate($request->validated('category_id'));
        $service->price_cents = $request->priceInCents();
        $service->save();
    }
}
