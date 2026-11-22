<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function home(): View
    {
        return view('marketplace.home', [
            'featuredServices' => $this->publishedServices()->limit(3)->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function index(Request $request): View
    {
        $categorySlug = $request->query('category');
        $keyword = $request->query('q', '');
        abort_unless(is_string($keyword) && mb_strlen($keyword) <= 100, 422, 'Search must be 100 characters or fewer.');

        $keyword = trim($keyword);
        $pattern = '%'.strtr($keyword, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
        abort_unless($categorySlug === null || is_string($categorySlug), 404);

        $category = $categorySlug === null
            ? null
            : Category::where('slug', $categorySlug)->firstOrFail();

        return view('marketplace.index', [
            'services' => $this->publishedServices()
                ->when($category, fn (Builder $query): Builder => $query->whereBelongsTo($category))
                ->when($keyword !== '', fn (Builder $query): Builder => $query->where(
                    fn (Builder $query): Builder => $query
                        ->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("description LIKE ? ESCAPE '!'", [$pattern]),
                ))
                ->paginate(9)
                ->appends(array_filter(['category' => $category?->slug, 'q' => $keyword], fn (?string $value): bool => $value !== null && $value !== '')),
            'categories' => Category::orderBy('name')->get(),
            'category' => $category,
            'keyword' => $keyword,
        ]);
    }

    public function show(string $service): View
    {
        $listing = $this->publishedServices()->findOrFail($service);

        $gallery = [];
        if ($listing->image_path && str_starts_with($listing->image_path, 'demo-services/')) {
            foreach ([2, 3] as $position) {
                $path = dirname($listing->image_path).'/'.$position.'.webp';
                if (Storage::disk('public')->exists($path)) {
                    $gallery[] = Storage::disk('public')->url($path);
                }
            }
        }

        return view('marketplace.show', [
            'gallery' => $gallery,
            'service' => $listing,
            'reviews' => $listing->reviews()
                ->where('reviews.is_hidden', false)
                ->with('booking.customer')
                ->latest('reviews.id')
                ->paginate(5),
        ]);
    }

    /** @return Builder<Service> */
    private function publishedServices(): Builder
    {
        return Service::query()
            ->where('is_published', true)
            ->with(['category', 'provider'])
            ->latest('id');
    }
}
