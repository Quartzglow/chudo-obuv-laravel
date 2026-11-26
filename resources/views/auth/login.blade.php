@extends('layouts.app')

@section('title', 'Вход в систему')

@section('content')
    <div class="auth-card">
        <h1 class="page-title">Вход в систему</h1>
        <p class="page-lead">Введите логин, номер телефона или адрес электронной почты из списка пользователей системы и пароль.</p>

        <form method="POST" action="{{ route('login.store') }}" class="card">
            @csrf

            <div class="card-header">Авторизация</div>
            <div class="card-body">
                <div class="stack">
                    <div class="form-field">
                        <label for="identifier">Логин, телефон или e-mail</label>
                        <input id="identifier" type="text" name="identifier" value="{{ old('identifier') }}" required autofocus autocomplete="username" placeholder="isivanov">
                        @error('identifier') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-field">
                        <label for="password">Пароль</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password">
                        @error('password') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="checkbox-field" style="padding-top:0">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <label for="remember">Запомнить меня</label>
                    </div>

                    <div class="form-actions" style="margin-top:0">
                        <button type="submit" class="btn btn-accent">Войти</button>
                        <a class="btn btn-secondary" href="{{ route('catalog.index') }}">Каталог без входа</a>
                    </div>

                    <p class="mb-0">
                        <a href="{{ route('password.request') }}">Забыли пароль? Восстановить доступ</a>
                    </p>
                </div>
            </div>
        </form>

        <div class="demo-accounts">
            <strong>Демонстрационные учётные записи</strong> (пароль у всех — <code>password</code>):
            <ul>
                <li>Администратор — логин <code>isivanov</code>, e-mail <code>isivanov@chudo-obuv.ru</code>, телефон <code>+7 (900) 100-10-01</code></li>
                <li>Менеджер — логин <code>papetrov</code></li>
                <li>Авторизованный пользователь — логин <code>asidorova</code></li>
            </ul>
            <div class="field-hint mt-16">Регистрация не предусмотрена: вход возможен только для пользователей из списка системы.</div>
        </div>
    </div>
@endsection