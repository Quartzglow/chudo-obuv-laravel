@extends('layouts.app')

@section('title', 'Заказ № '.$order->id)

@section('content')
    <p class="muted"><a href="{{ route('orders.index') }}">← К списку моих заказов</a></p>

    <h1 class="page-title">Заказ № {{ $order->id }}</h1>

    <div class="card">
        <div class="card-header">Ключевая информация</div>
        <div class="card-body">
            <dl class="spec-list">
                <dt>Дата заказа</dt>
                <dd>{{ $order->ordered_at_label }}</dd>
                <dt>ФИО клиента</dt>
                <dd>{{ $order->customer_name }}</dd>
                <dt>Количество позиций</dt>
                <dd>{{ $order->items->count() }}</dd>
                <dt>Итоговая сумма</dt>
                <dd><strong>@money($order->total)</strong></dd>
            </dl>
        </div>
    </div>

    <h2 class="section-title">Состав заказа</h2>
    <div class="card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Модель</th>
                        <th>Производство</th>
                        <th>Размер</th>
                        <th class="numeric">Количество</th>
                        <th class="numeric">Цена за единицу</th>
                        <th class="numeric">Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>
                                @if ($item->product)
                                    <a href="{{ route('catalog.show', $item->product) }}">{{ $item->product_name }}</a>
                                @else
                                    {{ $item->product_name }}
                                @endif
                            </td>
                            <td>{{ $item->brand }}</td>
                            <td>{{ $item->size_label }}</td>
                            <td class="numeric">{{ $item->quantity }}</td>
                            <td class="numeric">@money($item->unit_price)</td>
                            <td class="numeric">@money($item->line_total)</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right">Итого по заказу:</td>
                        <td class="numeric">@money($order->total)</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection