@extends('layouts.app')

@section('title', 'Новый пароль')

@section('content')
    <div class="auth-card">
        <h1 class="page-title">Установка нового пароля</h1>
        <p class="page-lead">Задайте новый пароль для входа в систему (не менее 6 символов).</p>

        <form method="POST" action="{{ route('password.store') }}" class="card">
            @csrf

            <div class="card-header">Новый пароль</div>
            <div class="card-body">
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="stack">
                    <div class="form-field">
                        <label for="email">Адрес электронной почты</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required>
                        @error('email') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-field">
                        <label for="password">Новый пароль</label>
                        <input id="password" type="password" name="password" required autocomplete="new-password">
                        @error('password') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-field">
                        <label for="password_confirmation">Подтверждение пароля</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                    </div>

                    <div class="form-actions" style="margin-top:0">
                        <button type="submit" class="btn btn-accent">Сохранить новый пароль</button>
                        <a class="btn btn-secondary" href="{{ route('login') }}">Отмена</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection