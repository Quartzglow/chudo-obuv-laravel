@extends('layouts.app')

@section('title', 'Корзина')

@section('content')
    <h1 class="page-title">Корзина</h1>
    <p class="page-lead">Проверьте состав заказа, при необходимости измените количество или удалите товарные позиции.</p>

    @if ($rows === [])
        <div class="card">
            <div class="card-body">
                Корзина пуста. Перейдите в <a href="{{ route('catalog.index') }}">каталог товаров</a>, чтобы выбрать модель и размер.
            </div>
        </div>
    @else
        <div class="card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Модель</th>
                            <th>Размер</th>
                            <th class="numeric">Цена за единицу</th>
                            <th>Количество</th>
                            <th class="numeric">Сумма</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('catalog.show', $row['product']) }}">{{ $row['product']->name }}</a>
                                    <div class="muted">{{ $row['product']->brand }}</div>
                                </td>
                                <td>{{ $row['size']->label }}</td>
                                <td class="numeric">@money($row['product']->price)</td>
                                <td>
                                    <form method="POST" action="{{ route('cart.update', $row['product']) }}" class="quantity-form">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="size_id" value="{{ $row['size']->id }}">
                                        <input class="quantity-input" type="number" name="quantity" min="0" max="{{ $row['available'] }}" value="{{ $row['quantity'] }}" aria-label="Количество">
                                        <button type="submit" class="btn btn-secondary btn-small">Обновить</button>
                                    </form>
                                    <div class="field-hint">Доступно: {{ $row['available'] }} шт.</div>
                                </td>
                                <td class="numeric">@money($row['line_total'])</td>
                                <td>
                                    <form method="POST" action="{{ route('cart.remove', $row['product']) }}" class="inline-form">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="size_id" value="{{ $row['size']->id }}">
                                        <button type="submit" class="btn btn-danger btn-small">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="cart-summary">
            <div>
                <div class="cart-total">Итого: @money($total)</div>
                <div class="field-hint">Взаиморасчёты осуществляются в рублях.</div>
            </div>
            <div class="form-actions" style="margin-top:0">
                <form method="POST" action="{{ route('orders.store') }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-accent">Оформить заказ</button>
                </form>
                <form method="POST" action="{{ route('cart.clear') }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Очистить корзину</button>
                </form>
                <a class="btn btn-outline" href="{{ route('catalog.index') }}">Продолжить выбор</a>
            </div>
        </div>
    @endif
@endsection