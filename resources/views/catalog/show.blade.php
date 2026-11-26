@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <p class="muted"><a href="{{ route('catalog.index') }}">← Вернуться в каталог</a></p>

    <h1 class="page-title">{{ $product->name }}</h1>

    <p>
        <span class="badge badge-ok">{{ $product->category?->parent?->name }} · {{ $product->category?->name }}</span>
        @if ($product->isLowStock())
            <span class="badge badge-low">Осталось {{ $product->total_stock }} шт. суммарно по всем размерам</span>
        @endif
    </p>

    <div class="product-detail mt-16">
        <div>
            <img class="product-detail-image" src="{{ $product->imageUrl() }}" alt="Изображение модели «{{ $product->name }}»">
        </div>

        <div>
            <dl class="spec-list">
                <dt>Производство</dt>
                <dd>{{ $product->brand }}</dd>
                <dt>Категория</dt>
                <dd>{{ $product->category?->parent?->name }} / {{ $product->category?->name }}</dd>
                <dt>Цена</dt>
                <dd><strong>@money($product->price)</strong></dd>
                <dt>Доступно для заказа</dt>
                <dd>{{ $product->total_stock }} шт. суммарно по всем размерам</dd>
            </dl>

            <h2 class="section-title">Описание</h2>
            <p>{{ $product->description }}</p>

            <h2 class="section-title">Состав</h2>
            <p>{{ $product->composition }}</p>

            <h2 class="section-title">Размерный ряд и доступные для заказа размеры</h2>
            <ul class="size-chips">
                @foreach ($product->sizes as $size)
                    <li class="size-chip @if ($size->pivot->quantity <= 0) is-empty @endif">
                        {{ $size->label }}
                        <small>— {{ $size->pivot->quantity }} шт.</small>
                    </li>
                @endforeach
            </ul>

            <h2 class="section-title">Заказ</h2>

            @auth
                @php($availableSizes = $product->sizes->filter(fn ($size) => $size->pivot->quantity > 0))
                @if ($availableSizes->isEmpty())
                    <div class="alert alert-error">В настоящий момент нет доступных для заказа размеров этой модели.</div>
                @else
                    <form method="POST" action="{{ route('cart.add', $product) }}">
                        @csrf
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="size_id">Размер</label>
                                <select id="size_id" name="size_id" required>
                                    @foreach ($availableSizes as $size)
                                        <option value="{{ $size->id }}">{{ $size->label }} — доступно {{ $size->pivot->quantity }} шт.</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="quantity">Количество, пар</label>
                                <input type="number" id="quantity" name="quantity" min="1" max="999" value="1" required>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-accent">Добавить в корзину</button>
                            <a class="btn btn-secondary" href="{{ route('cart.index') }}">Перейти в корзину</a>
                        </div>
                    </form>
                @endif
            @else
                <div class="alert alert-success">
                    Чтобы добавить товар в корзину и оформить заказ, <a href="{{ route('login') }}">войдите в систему</a>.
                    Просмотр каталога доступен без авторизации.
                </div>
            @endauth
        </div>
    </div>
@endsection