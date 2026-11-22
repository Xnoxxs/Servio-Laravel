<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
        abort_unless($categorySlug === null || is_string($categorySlug), 404);

        $category = $categorySlug === null
            ? null
            : Category::where('slug', $categorySlug)->firstOrFail();

        return view('marketplace.index', [
            'services' => $this->publishedServices()
                ->when($category, fn (Builder $query): Builder => $query->whereBelongsTo($category))
                ->paginate(9)
                ->appends($category ? ['category' => $category->slug] : []),
            'categories' => Category::orderBy('name')->get(),
            'category' => $category,
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
