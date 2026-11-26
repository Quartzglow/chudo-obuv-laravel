@extends('layouts.app')

@section('title', 'Редактирование данных профиля')

@section('content')
    <h1 class="page-title">Редактирование данных профиля</h1>
    <p class="page-lead">Измените свои данные и сохраните изменения.</p>

    <form method="POST" action="{{ route('profile.update') }}" class="card">
        @csrf
        @method('PATCH')

        <div class="card-header">Личные данные</div>
        <div class="card-body">
            <div class="form-grid">
                <div class="form-field">
                    <label for="surname">Фамилия</label>
                    <input id="surname" type="text" name="surname" value="{{ old('surname', $user->surname) }}" required>
                    @error('surname') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="name">Имя</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="patronymic">Отчество</label>
                    <input id="patronymic" type="text" name="patronymic" value="{{ old('patronymic', $user->patronymic) }}">
                    @error('patronymic') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="login">Логин</label>
                    <input id="login" type="text" name="login" value="{{ old('login', $user->login) }}" required>
                    @error('login') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="email">Адрес электронной почты</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="phone">Номер телефона</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}">
                    @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-accent">Сохранить</button>
                <a class="btn btn-secondary" href="{{ route('profile.show') }}">Отмена</a>
            </div>
        </div>
    </form>
@endsection