<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Каталог товаров. Фильтрация, сортировка и поиск доступны только авторизованным пользователям.
     */
    public function index(Request $request): View
    {
        $canFilter = $request->user() !== null;

        $filters = $canFilter
            ? $request->only(['q', 'category_id', 'brand', 'size_id', 'price_min', 'price_max', 'sort', 'in_stock'])
            : [];

        $products = Product::query()
            ->with(['category.parent', 'sizes'])
            ->filter($filters)
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()
            ->with(['children' => fn ($query) => $query->whereHas('products')])
            ->whereNull('parent_id')
            ->whereHas('children')
            ->orderBy('name')
            ->get();

        $brands = Product::query()->distinct()->orderBy('brand')->pluck('brand', 'brand');

        $sizes = Size::query()
            ->whereHas('products', fn ($query) => $query->where('product_sizes.quantity', '>', 0))
            ->orderBy('value')
            ->get();

        return view('catalog.index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'sizes' => $sizes,
            'canFilter' => $canFilter,
            'filters' => $filters,
        ]);
    }

    public function show(Product $product): View
    {
        $product->load(['category.parent', 'sizes']);

        return view('catalog.show', ['product' => $product]);
    }
}
