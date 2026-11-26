#!/usr/bin/env bash
# Установка проекта «Чудо Обувь» на Linux/macOS.
# Запуск: bash scripts/setup.sh
set -euo pipefail

echo "== 1/4 Проверка PHP и Composer =="
php -v | head -n 1
composer -V | head -n 1

echo "== 2/4 Установка PHP-зависимостей (composer install) =="
composer install --no-interaction

echo "== 3/4 Файл окружения и ключ приложения =="
env_created=0
if [ ! -f .env ]; then
    cp .env.example .env
    env_created=1
fi
if [ "$env_created" -eq 1 ] || ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --ansi
fi

echo "== 4/4 Миграции и импорт данных из ресурсов =="
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi
php artisan migrate:fresh --seed --force --ansi

echo
echo "Готово! Запуск приложения: php artisan serve"
echo "Откройте http://127.0.0.1:8000"
echo "Вход: isivanov / papetrov / asidorova, пароль: password"