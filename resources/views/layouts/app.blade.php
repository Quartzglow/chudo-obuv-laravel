@php
    $cartCount = auth()->check() ? app(\App\Services\Cart::class)->count() : 0;
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Каталог товаров') — Чудо Обувь</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('head')
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="{{ route('home') }}">
            {{-- Логотип компании: изображение не искажается, пропорции сохранены --}}
            <img class="brand-logo" src="{{ asset('images/logo.png') }}" alt="Логотип торговой компании «Чудо Обувь»" width="56" height="56">
            <span>
                <span class="brand-name">Чудо Обувь</span>
                <span class="brand-tagline">Сеть магазинов обуви</span>
            </span>
        </a>

        <nav class="main-nav" aria-label="Основная навигация">
            <a href="{{ route('catalog.index') }}" @class(['is-active' => request()->routeIs('catalog.*')])>Каталог</a>

            @auth
                <a href="{{ route('cart.index') }}" @class(['is-active' => request()->routeIs('cart.*'), 'nav-cart' => true])>
                    Корзина ({{ $cartCount }})
                </a>
                <a href="{{ route('orders.index') }}" @class(['is-active' => request()->routeIs('orders.*')])>Мои заказы</a>
                <a href="{{ route('profile.show') }}" @class(['is-active' => request()->routeIs('profile.*')])>Профиль</a>

                @if (auth()->user()->canManageOrders())
                    <a href="{{ route('manage.orders.index') }}" @class(['is-active' => request()->routeIs('manage.*')])>Управление заказами</a>
                @endif

                <form class="inline-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="link-button">Выйти</button>
                </form>
            @else
                <a href="{{ route('login') }}" @class(['is-active' => request()->routeIs('login')])>Вход</a>
            @endauth
        </nav>
    </div>

    @auth
        <div class="container user-panel">
            <span>{{ auth()->user()->full_name }}</span>
            <span class="role-badge">{{ auth()->user()->roleLabel() }}</span>
            <span class="muted">Взаиморасчёты осуществляются в рублях</span>
        </div>
    @endauth
</header>

<main class="container main-content">
    @include('partials.flash')

    @yield('content')
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <span>© {{ date('Y') }} Торговая компания «Чудо Обувь». Продажа обуви и сеть магазинов.</span>
        <span>Взаиморасчёты с клиентами — в рублях</span>
    </div>
</footer>

@stack('scripts')
</body>
</html>