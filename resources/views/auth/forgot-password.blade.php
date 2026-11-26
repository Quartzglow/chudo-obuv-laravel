@extends('layouts.app')

@section('title', 'Восстановление пароля')

@section('content')
    <div class="auth-card">
        <h1 class="page-title">Восстановление пароля</h1>
        <p class="page-lead">Укажите логин, телефон или e-mail, чтобы получить ссылку для установки нового пароля.</p>

        <form method="POST" action="{{ route('password.email') }}" class="card">
            @csrf

            <div class="card-header">Восстановление доступа</div>
            <div class="card-body">
                <div class="form-field">
                    <label for="identifier">Логин, телефон или e-mail</label>
                    <input id="identifier" type="text" name="identifier" value="{{ old('identifier') }}" required autofocus>
                    @error('identifier') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-accent">Получить ссылку</button>
                    <a class="btn btn-secondary" href="{{ route('login') }}">Вернуться к входу</a>
                </div>
            </div>
        </form>

        @if (session('reset_link'))
            <div class="alert alert-success mt-24">
                <strong>Демонстрационный режим:</strong> отправка писем в приложении не настроена, поэтому ссылка для
                восстановления пароля выводится здесь. Ссылка действует 60 минут.
                <a class="reset-link" href="{{ session('reset_link') }}">{{ session('reset_link') }}</a>
            </div>
        @endif
    </div>
@endsection