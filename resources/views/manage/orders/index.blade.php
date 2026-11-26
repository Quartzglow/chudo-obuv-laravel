@extends('layouts.app')

@section('title', 'Управление заказами')

@section('content')
    <div class="form-actions" style="margin-top:0; justify-content: space-between; align-items: flex-start">
        <div>
            <h1 class="page-title">Управление заказами</h1>
            <p class="page-lead mb-0">
                Просмотр заказов и их состава, добавление и удаление заказов.
                @if (auth()->user()->isAdmin())
                    Администратор также может редактировать заказы.
                @else
                    Редактирование заказов доступно администратору.
                @endif
            </p>
        </div>
        <a class="btn btn-accent" href="{{ route('manage.orders.create') }}">Добавить заказ</a>
    </div>

    <form method="GET" action="{{ route('manage.orders.index') }}" class="card mt-24">
        <div class="card-header">Поиск и фильтрация заказов</div>
        <div class="card-body">
            <div class="filters">
                <div class="form-field">
                    <label for="q">ФИО клиента или номер заказа</label>
                    <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}">
                </div>
                <div class="form-field">
                    <label for="date_from">Дата заказа с</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="form-field">
                    <label for="date_to">Дата заказа по</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="form-field">
                    <label for="sort">Сортировка</label>
                    <select id="sort" name="sort">
                        <option value="date_desc" @selected(($filters['sort'] ?? 'date_desc') === 'date_desc')>Дата: новые сначала</option>
                        <option value="date_asc" @selected(($filters['sort'] ?? '') === 'date_asc')>Дата: старые сначала</option>
                        <option value="total_desc" @selected(($filters['sort'] ?? '') === 'total_desc')>Сумма: по убыванию</option>
                        <option value="total_asc" @selected(($filters['sort'] ?? '') === 'total_asc')>Сумма: по возрастанию</option>
                    </select>
                </div>
                <div class="filters-actions">
                    <button type="submit" class="btn btn-accent">Применить</button>
                    <a class="btn btn-secondary" href="{{ route('manage.orders.index') }}">Сбросить</a>
                </div>
            </div>
        </div>
    </form>

    <h2 class="section-title">Заказы ({{ $orders->total() }})</h2>

    @if ($orders->isEmpty())
        <div class="card">
            <div class="card-body">Заказы не найдены.</div>
        </div>
    @else
        <div class="card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Номер заказа</th>
                            <th>Дата заказа</th>
                            <th>ФИО клиента</th>
                            <th>Позиций</th>
                            <th class="numeric">Итоговая сумма</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>№ {{ $order->id }}</td>
                                <td class="nowrap">{{ $order->ordered_at_label }}</td>
                                <td>{{ $order->customer_name }}</td>
                                <td>{{ $order->items_count }}</td>
                                <td class="numeric">@money($order->total)</td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-secondary btn-small" href="{{ route('manage.orders.show', $order) }}">Состав</a>

                                        @if (auth()->user()->isAdmin())
                                            <a class="btn btn-outline btn-small" href="{{ route('manage.orders.edit', $order) }}">Изменить</a>
                                        @endif

                                        <form method="POST" action="{{ route('manage.orders.destroy', $order) }}" class="inline-form"
                                              onsubmit="return confirm('Удалить заказ № {{ $order->id }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-small">Удалить</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{ $orders->links() }}
    @endif
@endsection