# -*- coding: utf-8 -*-
"""
Импорт исходных данных «Чудо Обувь» из xlsx в database/data/*.json
и копирование графических ассетов в public/.

Запуск:
    python _import.py

Скрипт идемпотентен: перезаписывает JSON и ассеты, ничего больше не трогает.
"""
import hashlib
import json
import os
import shutil
import sys
from datetime import date, datetime

import openpyxl

SRC = r"C:\Users\skerd\Desktop\БУ\Задание 1\Прил_2_ОЗ_КИМ_09.02.07-2-2027"
PROJECT = r"C:\Users\skerd\Desktop\tapochki\chudo_obuv"
DATA = os.path.join(PROJECT, "database", "data")
PUBLIC_IMAGES = os.path.join(PROJECT, "public", "images")
PUBLIC_PRODUCTS = os.path.join(PUBLIC_IMAGES, "products")

ROLE_MAP = {
    "Администратор": "admin",
    "Менеджер": "manager",
    "Авторизованный пользователь": "user",
}


def norm(value):
    """Строковое значение ячейки без служебных пробелов по краям."""
    if value is None:
        return ""
    if isinstance(value, str):
        return value
    return str(value)


def as_int(value):
    if isinstance(value, bool):
        raise ValueError(f"Ожидалось число, получено bool: {value!r}")
    if isinstance(value, int):
        return value
    if isinstance(value, float):
        if value.is_integer():
            return int(value)
        raise ValueError(f"Нецелое число: {value!r}")
    if isinstance(value, str):
        return int(value.strip())
    raise ValueError(f"Ожидалось число, получено {value!r}")


def as_number(value):
    """Число размера: int для целых, float для половинных (36.5, 37.5, ...)."""
    if isinstance(value, bool):
        raise ValueError(f"Ожидалось число, получено bool: {value!r}")
    if isinstance(value, int):
        return value
    if isinstance(value, float):
        return int(value) if value.is_integer() else value
    if isinstance(value, str):
        text = value.strip().replace(",", ".")
        number = float(text)
        return int(number) if number.is_integer() else number
    raise ValueError(f"Ожидалось число, получено {value!r}")


def as_date(value):
    if isinstance(value, datetime):
        return value.strftime("%Y-%m-%d")
    if isinstance(value, date):
        return value.isoformat()
    if isinstance(value, str):
        return value.strip()[:10]
    raise ValueError(f"Ожидалась дата, получено {value!r}")


def rows(path):
    wb = openpyxl.load_workbook(path, data_only=True, read_only=True)
    ws = wb.worksheets[0]
    header = None
    for r_idx, row in enumerate(ws.iter_rows(values_only=True), 1):
        if r_idx == 1:
            header = [norm(c).strip() for c in row]
            continue
        if row is None or all(c is None or (isinstance(c, str) and not c.strip()) for c in row):
            continue
        yield header, row
    wb.close()


def col(header, row, name):
    try:
        idx = header.index(name)
    except ValueError:
        raise ValueError(f"Нет колонки {name!r}, есть {header!r}")
    return row[idx]


def write_json(name, payload):
    path = os.path.join(DATA, name)
    with open(path, "w", encoding="utf-8", newline="\n") as fh:
        json.dump(payload, fh, ensure_ascii=False, indent=2)
        fh.write("\n")
    return path


def sha256(path):
    h = hashlib.sha256()
    with open(path, "rb") as fh:
        for chunk in iter(lambda: fh.read(1 << 20), b""):
            h.update(chunk)
    return h.hexdigest()


def build_products():
    result = []
    for header, row in rows(os.path.join(SRC, "Products_import.xlsx")):
        result.append({
            "category": norm(col(header, row, "Категория")),
            "subcategory": norm(col(header, row, "Подкатегория")),
            "image": norm(col(header, row, "Изображение")),
            "name": norm(col(header, row, "Наименование товара")),
            "brand": norm(col(header, row, "Производство")),
            "description": norm(col(header, row, "Описание")),
            "composition": norm(col(header, row, "Состав")),
            "price": as_int(col(header, row, "Цена")),
        })
    return result


def build_sizes():
    return [as_number(col(header, row, "Размер"))
            for header, row in rows(os.path.join(SRC, "Sizes_import.xlsx"))]


def build_stock():
    result = []
    for header, row in rows(os.path.join(SRC, "Stock_Items_import.xlsx")):
        result.append({
            "product": norm(col(header, row, "Наименование товара")),
            "brand": norm(col(header, row, "Производство")),
            "size": as_number(col(header, row, "Размер")),
            "quantity": as_int(col(header, row, "Количество доступное для заказа")),
        })
    return result


def build_users():
    result = []
    for header, row in rows(os.path.join(SRC, "Users_import.xlsx")):
        role_raw = norm(col(header, row, "Роль")).strip()
        if role_raw not in ROLE_MAP:
            raise ValueError(f"Неизвестная роль {role_raw!r}")
        result.append({
            "surname": norm(col(header, row, "Фамилия")),
            "name": norm(col(header, row, "Имя")),
            "patronymic": norm(col(header, row, "Отчетсво")),
            "login": norm(col(header, row, "Логин")),
            "role": ROLE_MAP[role_raw],
        })
    return result


def build_orders():
    orders = []
    by_number = {}
    for header, row in rows(os.path.join(SRC, "Orders_import.xlsx")):
        number = as_int(col(header, row, "Номер заказа"))
        if number not in by_number:
            order = {
                "number": number,
                "date": as_date(col(header, row, "Дата заказа")),
                "customer": norm(col(header, row, "ФИО")),
                "items": [],
            }
            by_number[number] = order
            orders.append(order)
        by_number[number]["items"].append({
            "product": norm(col(header, row, "Наименование товара")),
            "brand": norm(col(header, row, "Производство")),
            "size": as_number(col(header, row, "Размер")),
            "quantity": as_int(col(header, row, "Количество")),
            "unit_price": as_int(col(header, row, "Цена за единицу")),
        })
    return orders


def copy_assets():
    os.makedirs(PUBLIC_PRODUCTS, exist_ok=True)
    os.makedirs(PUBLIC_IMAGES, exist_ok=True)
    copied = []
    img_src = os.path.join(SRC, "Images")
    for entry in sorted(os.listdir(img_src)):
        s = os.path.join(img_src, entry)
        if not os.path.isfile(s) or not entry.lower().endswith(".png"):
            continue
        dst = os.path.join(PUBLIC_PRODUCTS, entry)
        shutil.copyfile(s, dst)
        copied.append(dst)
    pairs = [
        ("Чудо Обувь.png", os.path.join(PROJECT, "public", "images", "logo.png")),
        ("Чудо Обувь.ico", os.path.join(PROJECT, "public", "favicon.ico")),
        ("Чудо Обувь.ico", os.path.join(PROJECT, "public", "images", "favicon.ico")),
        ("picture.png", os.path.join(PROJECT, "public", "images", "picture.png")),
    ]
    for src_name, dst in pairs:
        shutil.copyfile(os.path.join(SRC, src_name), dst)
        copied.append(dst)
    return copied


def main():
    os.makedirs(DATA, exist_ok=True)
    products = build_products()
    sizes = build_sizes()
    stock = build_stock()
    users = build_users()
    orders = build_orders()

    write_json("products.json", products)
    write_json("sizes.json", sizes)
    write_json("stock.json", stock)
    write_json("users.json", users)
    write_json("orders.json", orders)
    copied = copy_assets()

    print("IMPORT OK")
    print("products", len(products))
    print("sizes", len(sizes))
    print("stock", len(stock))
    print("users", len(users))
    print("orders", len(orders), "items", sum(len(o["items"]) for o in orders))
    print("assets copied", len(copied))


if __name__ == "__main__":
    main()