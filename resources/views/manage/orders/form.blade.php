@extends('layouts.app')

@section('title', $order ? 'Редактирование заказа № '.$order->id : 'Новый заказ')

@php($formTitle = $order ? 'Редактирование заказа № '.$order->id : 'Новый заказ')
@php($formAction = $order ? route('manage.orders.update', $order) : route('manage.orders.store'))
@php($formItems = old('items', $initialItems))

@section('content')
    <p class="muted"><a href="{{ route('manage.orders.index') }}">← К списку заказов</a></p>

    <h1 class="page-title">{{ $formTitle }}</h1>
    <p class="page-lead">Укажите ФИО клиента и дату заказа, добавьте товарные позиции с размером и количеством.</p>

    <form method="POST" action="{{ $formAction }}" class="card">
        @csrf
        @if ($order)
            @method('PUT')
        @endif

        <div class="card-header">Данные заказа</div>
        <div class="card-body">
            <div class="form-grid">
                <div class="form-field">
                    <label for="customer_name">ФИО клиента</label>
                    <input id="customer_name" type="text" name="customer_name" list="customer-names"
                           value="{{ old('customer_name', $order?->customer_name) }}" required>
                    <datalist id="customer-names">
                        @foreach ($customers as $customer)
                            <option value="{{ $customer }}"></option>
                        @endforeach
                    </datalist>
                    @error('customer_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-field">
                    <label for="ordered_at">Дата заказа</label>
                    <input id="ordered_at" type="date" name="ordered_at"
                           value="{{ old('ordered_at', $order?->ordered_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                    @error('ordered_at') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <h2 class="section-title">Товарные позиции</h2>

            @error('items') <div class="field-error">{{ $message }}</div> @enderror
            @error('items.*.product_id') <div class="field-error">{{ $message }}</div> @enderror
            @error('items.*.size') <div class="field-error">{{ $message }}</div> @enderror
            @error('items.*.quantity') <div class="field-error">{{ $message }}</div> @enderror

            <div class="table-wrap">
                <table class="order-form-table">
                    <thead>
                        <tr>
                            <th>Модель</th>
                            <th>Размер</th>
                            <th>Количество</th>
                            <th>Цена за единицу</th>
                            <th>Сумма</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="order-items"></tbody>
                </table>
            </div>

            <button type="button" id="add-order-item" class="btn btn-secondary mt-16">Добавить товарную позицию</button>

            <div class="order-form-total">Итого: <span id="order-total">0 ₽</span></div>
            <p class="field-hint">Количество ограничено доступным для заказа остатком выбранного размера.</p>
        </div>

        <div class="card-body" style="border-top:1px solid var(--color-border)">
            <div class="form-actions" style="margin-top:0">
                <button type="submit" class="btn btn-accent">Сохранить заказ</button>
                <a class="btn btn-secondary" href="{{ route('manage.orders.index') }}">Отмена</a>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        window.ORDER_FORM = {
            mode: '{{ $order ? 'edit' : 'create' }}',
            products: @json($products),
            initialItems: @json($formItems),
        };
    </script>
    <script src="{{ asset('js/order-form.js') }}"></script>
@endpush