@extends('layouts.app')

@section('title', 'Каталог товаров')

@section('content')
    <section class="home-hero">
        <img class="home-hero-logo" src="{{ asset('images/logo.png') }}" alt="Логотип торговой компании «Чудо Обувь»">
        <div class="home-hero-text">
            <h1 class="page-title">Каталог товаров</h1>
            <p>Выберите модель и доступный размер обуви, чтобы оформить заказ.</p>
            <p class="hero-note">Товары, доступное для заказа количество которых суммарно по всем размерам не превышает трёх пар, подсвечены красным цветом.</p>
        </div>
    </section>

    @if ($canFilter)
        <form method="GET" action="{{ route('catalog.index') }}" class="card">
            <div class="card-header">Фильтрация, сортировка и поиск</div>
            <div class="card-body">
                <div class="filters">
                    <div class="form-field">
                        <label for="q">Поиск по названию, производству, описанию</label>
                        <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Например, кроссовки">
                    </div>

                    <div class="form-field">
                        <label for="category_id">Категория / подкатегория</label>
                        <select id="category_id" name="category_id">
                            <option value="">Все категории</option>
                            @foreach ($categories as $category)
                                <optgroup label="{{ $category->name }}">
                                    @foreach ($category->children as $child)
                                        <option value="{{ $child->id }}" @selected((int) ($filters['category_id'] ?? 0) === $child->id)>
                                            {{ $child->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="brand">Производство</label>
                        <select id="brand" name="brand">
                            <option value="">Все производители</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand }}" @selected(($filters['brand'] ?? '') === $brand)>{{ $brand }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="size_id">Размер</label>
                        <select id="size_id" name="size_id">
                            <option value="">Любой размер</option>
                            @foreach ($sizes as $size)
                                <option value="{{ $size->id }}" @selected((int) ($filters['size_id'] ?? 0) === $size->id)>
                                    {{ $size->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="price_min">Цена от, ₽</label>
                        <input type="number" id="price_min" name="price_min" min="0" step="1" value="{{ $filters['price_min'] ?? '' }}">
                    </div>

                    <div class="form-field">
                        <label for="price_max">Цена до, ₽</label>
                        <input type="number" id="price_max" name="price_max" min="0" step="1" value="{{ $filters['price_max'] ?? '' }}">
                    </div>

                    <div class="form-field">
                        <label for="sort">Сортировка</label>
                        <select id="sort" name="sort">
                            <option value="" @selected(($filters['sort'] ?? '') === '')>По умолчанию</option>
                            <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Цена по возрастанию</option>
                            <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Цена по убыванию</option>
                            <option value="name_asc" @selected(($filters['sort'] ?? '') === 'name_asc')>Название А–Я</option>
                            <option value="name_desc" @selected(($filters['sort'] ?? '') === 'name_desc')>Название Я–А</option>
                        </select>
                    </div>

                    <div class="checkbox-field">
                        <input type="checkbox" id="in_stock" name="in_stock" value="1" @checked(! empty($filters['in_stock']))>
                        <label for="in_stock">Только в наличии</label>
                    </div>

                    <div class="filters-actions">
                        <button type="submit" class="btn btn-accent">Применить</button>
                        <a class="btn btn-secondary" href="{{ route('catalog.index') }}">Сбросить</a>
                    </div>
                </div>
            </div>
        </form>
    @else
        <div class="alert alert-success">
            Каталог доступен для просмотра без авторизации.
            Фильтрация, сортировка и поиск доступны после <a href="{{ route('login') }}">входа в систему</a>.
        </div>
    @endif

    <h2 class="section-title">Модели обуви ({{ $products->total() }})</h2>

    @if ($products->isEmpty())
        <div class="card">
            <div class="card-body">
                По заданным условиям товары не найдены. Измените параметры фильтрации.
            </div>
        </div>
    @else
        <div class="products-grid">
            @foreach ($products as $product)
                <article class="product-card @if ($product->isLowStock()) is-low-stock @endif">
                    <div class="product-image-frame">
                        <img class="product-image" src="{{ $product->imageUrl() }}" alt="Изображение модели «{{ $product->name }}»" loading="lazy">
                    </div>
                    <div class="product-card-body">
                        <h3 class="product-name">
                            <a href="{{ route('catalog.show', $product) }}">{{ $product->name }}</a>
                        </h3>
                        <div class="product-brand">Производство: {{ $product->brand }}</div>
                        <div class="product-meta">
                            {{ $product->category?->parent?->name }} · {{ $product->category?->name }}
                        </div>
                        <div class="product-meta">
                            Доступные размеры:
                            @forelse ($product->available_sizes as $size)
                                {{ $size->label }}@if (! $loop->last), @endif
                            @empty
                                нет
                            @endforelse
                        </div>
                        <div class="product-stock">
                            @if ($product->total_stock <= 0)
                                <span class="badge badge-out">Нет в наличии</span>
                            @elseif ($product->isLowStock())
                                <span class="badge badge-low">Осталось {{ $product->total_stock }} шт. по всем размерам</span>
                            @else
                                <span class="badge badge-ok">В наличии {{ $product->total_stock }} шт.</span>
                            @endif
                        </div>
                        <div class="product-price">@money($product->price)</div>
                        <a class="btn btn-accent btn-small" href="{{ route('catalog.show', $product) }}">Подробнее и заказ</a>
                    </div>
                </article>
            @endforeach
        </div>

        {{ $products->links() }}
    @endif
@endsection