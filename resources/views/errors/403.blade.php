@extends('layouts.app')

@section('title', 'Доступ запрещён')

@section('content')
    <h1 class="page-title">Доступ запрещён</h1>
    <p class="page-lead">Недостаточно прав для выполнения этого действия.</p>

    <div class="card">
        <div class="card-body">
            <p>Раздел доступен только пользователям с соответствующей ролью.</p>
            <div class="form-actions">
                <a class="btn btn-accent" href="{{ route('home') }}">Перейти в каталог</a>
                @auth
                    <a class="btn btn-secondary" href="{{ route('profile.show') }}">Мой профиль</a>
                @else
                    <a class="btn btn-secondary" href="{{ route('login') }}">Войти в систему</a>
                @endauth
            </div>
        </div>
    </div>
@endsection