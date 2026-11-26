@extends('layouts.app')

@section('title', 'Мои заказы')

@section('content')
    <h1 class="page-title">Мои заказы</h1>
    <p class="page-lead">История заказов, оформленных в вашем профиле.</p>

    @if ($orders->isEmpty())
        <div class="card">
            <div class="card-body">
                Заказы отсутствуют. Выберите обувь в <a href="{{ route('catalog.index') }}">каталоге</a> и оформите первый заказ.
            </div>
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
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>№ {{ $order->id }}</td>
                                <td class="nowrap">{{ $order->ordered_at_label }}</td>
                                <td>{{ $order->customer_name }}</td>
                                <td>{{ $order->items->count() }}</td>
                                <td class="numeric">@money($order->total)</td>
                                <td><a class="btn btn-secondary btn-small" href="{{ route('orders.show', $order) }}">Состав заказа</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{ $orders->links() }}
    @endif
@endsection