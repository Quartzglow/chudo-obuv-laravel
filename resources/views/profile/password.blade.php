@extends('layouts.app')

@section('title', 'Смена пароля')

@section('content')
    <h1 class="page-title">Смена пароля</h1>
    <p class="page-lead">Укажите текущий пароль и новый пароль (не менее 6 символов).</p>

    <form method="POST" action="{{ route('profile.password.update') }}" class="card">
        @csrf
        @method('PATCH')

        <div class="card-header">Пароль</div>
        <div class="card-body">
            <div class="form-grid">
                <div class="form-field">
                    <label for="current_password">Текущий пароль</label>
                    <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
                    @error('current_password') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="password">Новый пароль</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password">
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="password_confirmation">Подтверждение нового пароля</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-accent">Изменить пароль</button>
                <a class="btn btn-secondary" href="{{ route('profile.show') }}">Отмена</a>
            </div>
        </div>
    </form>

    <div class="alert alert-success mt-24">
        Забыли пароль и не можете войти? Выйдите из системы и воспользуйтесь восстановлением пароля на странице входа.
    </div>
@endsection