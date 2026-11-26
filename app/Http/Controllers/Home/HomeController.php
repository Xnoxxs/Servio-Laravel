<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Shared\Controller;
use App\Models\Category;
use App\Models\Service;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featuredServices = Service::query()->forPublicBrowsing()->limit(3)->get();
        $categories = Category::orderBy('name')->get();

        return view('home.index', compact('featuredServices', 'categories'));
    }
}
