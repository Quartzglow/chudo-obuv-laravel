@extends('layouts.app')

@section('title', 'Профиль')

@section('content')
    <h1 class="page-title">Профиль</h1>
    <p class="page-lead">Здесь можно посмотреть историю заказов, отредактировать свои данные и изменить пароль.</p>

    <div class="profile-header">
        <div>
            <p class="profile-name">{{ $user->full_name }}</p>
            <div class="muted">Логин: {{ $user->login }} · <span class="role-badge">{{ $user->roleLabel() }}</span></div>
        </div>
        <div class="form-actions" style="margin-top:0">
            <a class="btn btn-accent" href="{{ route('profile.edit') }}">Редактировать данные</a>
            <a class="btn btn-secondary" href="{{ route('profile.password') }}">Сменить пароль</a>
        </div>
    </div>

    <div class="grid-two mt-24">
        <div class="card">
            <div class="card-header">Мои данные</div>
            <div class="card-body">
                <dl class="spec-list">
                    <dt>Фамилия</dt>
                    <dd>{{ $user->surname }}</dd>
                    <dt>Имя</dt>
                    <dd>{{ $user->name }}</dd>
                    <dt>Отчество</dt>
                    <dd>{{ $user->patronymic ?: '—' }}</dd>
                    <dt>Логин</dt>
                    <dd>{{ $user->login }}</dd>
                    <dt>Телефон</dt>
                    <dd>{{ $user->phone ?: '—' }}</dd>
                    <dt>E-mail</dt>
                    <dd>{{ $user->email }}</dd>
                    <dt>Роль</dt>
                    <dd>{{ $user->roleLabel() }}</dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Корзина</div>
            <div class="card-body">
                @if ($cartRows === [])
                    <p>Корзина пуста. Выберите обувь в <a href="{{ route('catalog.index') }}">каталоге товаров</a>.</p>
                @else
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Модель</th>
                                <th>Размер</th>
                                <th class="numeric">Кол-во</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cartRows as $row)
                                <tr>
                                    <td>{{ $row['product']->name }}</td>
                                    <td>{{ $row['size']->label }}</td>
                                    <td class="numeric">{{ $row['quantity'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">Итого</td>
                                <td class="numeric">@money($cartTotal)</td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="form-actions">
                        <a class="btn btn-accent" href="{{ route('cart.index') }}">Перейти в корзину и оформить заказ</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <h2 class="section-title">Последние заказы (всего: {{ $ordersCount }})</h2>

    @if ($lastOrders->isEmpty())
        <div class="card">
            <div class="card-body">Заказы отсутствуют. История заказов появится после оформления первого заказа.</div>
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
                        @foreach ($lastOrders as $order)
                            <tr>
                                <td>№ {{ $order->id }}</td>
                                <td class="nowrap">{{ $order->ordered_at_label }}</td>
                                <td>{{ $order->customer_name }}</td>
                                <td>{{ $order->items->count() }}</td>
                                <td class="numeric">@money($order->total)</td>
                                <td><a class="btn btn-secondary btn-small" href="{{ route('orders.show', $order) }}">Состав</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="form-actions">
            <a class="btn btn-outline" href="{{ route('orders.index') }}">Вся история заказов</a>
        </div>
    @endif
@endsection