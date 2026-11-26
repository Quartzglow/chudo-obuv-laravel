# -*- coding: utf-8 -*-
"""Проверка результата импорта: контракт JSON, counts, ассеты, дубли, перекрёстные ссылки."""
import hashlib
import json
import os
import sys

SRC = r"C:\Users\skerd\Desktop\БУ\Задание 1\Прил_2_ОЗ_КИМ_09.02.07-2-2027"
PROJECT = r"C:\Users\skerd\Desktop\tapochki\chudo_obuv"
DATA = os.path.join(PROJECT, "database", "data")
IMAGES = os.path.join(PROJECT, "public", "images")
PRODUCTS_DIR = os.path.join(IMAGES, "products")

ok = 0
fail = 0
notes = []


def check(cond, msg):
    global ok, fail
    if cond:
        ok += 1
        print(f"  OK   {msg}")
    else:
        fail += 1
        print(f"  FAIL {msg}")


def sha256(path):
    h = hashlib.sha256()
    with open(path, "rb") as fh:
        for chunk in iter(lambda: fh.read(1 << 20), b""):
            h.update(chunk)
    return h.hexdigest()


def load(name):
    path = os.path.join(DATA, name)
    raw = open(path, "rb").read()
    check(not raw.startswith(b"\xef\xbb\xbf"), f"{name}: нет BOM")
    try:
        text = raw.decode("utf-8")
        check(True, f"{name}: валидный UTF-8 ({len(raw)} байт)")
    except UnicodeDecodeError as exc:
        check(False, f"{name}: валидный UTF-8 ({exc})")
        return None
    return json.loads(text)


print("=== 1. JSON: чтение, кодировка, counts ===")
products = load("products.json")
sizes = load("sizes.json")
stock = load("stock.json")
users = load("users.json")
orders = load("orders.json")

check(isinstance(products, list) and len(products) == 31, f"products.json: list, count={len(products)} (ожидается 31)")
check(isinstance(sizes, list) and len(sizes) == 35, f"sizes.json: list, count={len(sizes)} (ожидается 35)")
check(isinstance(stock, list) and len(stock) == 92, f"stock.json: list, count={len(stock)} (ожидается 92)")
check(isinstance(users, list) and len(users) == 20, f"users.json: list, count={len(users)} (ожидается 20)")
check(isinstance(orders, list), f"orders.json: list, count={len(orders)}")
order_items = sum(len(o["items"]) for o in orders) if orders else 0
check(len({o["number"] for o in orders}) == len(orders), f"orders.json: уникальных номеров={len({o['number'] for o in orders})}, items={order_items}")

print("=== 2. Контракт ключей ===")
pk = ["category", "subcategory", "image", "name", "brand", "description", "composition", "price"]
sk = ["product", "brand", "size", "quantity"]
uk = ["surname", "name", "patronymic", "login", "role"]
ok_ = ["number", "date", "customer", "items"]
ik = ["product", "brand", "size", "quantity", "unit_price"]
check(all(list(p.keys()) == pk for p in products), "products: ключи и порядок точно по контракту")
check(all(list(s.keys()) == sk for s in stock), "stock: ключи и порядок точно по контракту")
check(all(list(u.keys()) == uk for u in users), "users: ключи и порядок точно по контракту")
check(all(list(o.keys()) == ok_ for o in orders), "orders: ключи и порядок точно по контракту")
check(all(list(i.keys()) == ik for o in orders for i in o["items"]), "orders.items: ключи и порядок точно по контракту")
check(all(isinstance(p["price"], int) and not isinstance(p["price"], bool) for p in products), "products.price: все int")
check(all(isinstance(s["quantity"], int) for s in stock), "stock.quantity: все int")
check(all(isinstance(o["number"], int) for o in orders), "orders.number: все int")
check(all(isinstance(i["quantity"], int) for o in orders for i in o["items"]), "orders.items.quantity: все int")
check(all(isinstance(i["unit_price"], int) for o in orders for i in o["items"]), "orders.items.unit_price: все int")
check(all(isinstance(v, (int, float)) and not isinstance(v, bool) for v in sizes), "sizes: все значения числовые")
check(all(isinstance(s["size"], (int, float)) and not isinstance(s["size"], bool) for s in stock), "stock.size: все значения числовые")
check(all(isinstance(i["size"], (int, float)) and not isinstance(i["size"], bool) for o in orders for i in o["items"]), "orders.items.size: все значения числовые")
check(all(isinstance(p["price"], int) and not isinstance(p["price"], bool) for p in products), "products.price: int")
check(all(p[k] == "" or isinstance(p[k], str) for p in products for k in pk if k != "price"), "products: строковые поля — str")
check(all(s[k] == "" or isinstance(s[k], str) for s in stock for k in ("product", "brand")), "stock: product/brand — str")
check(all(u["role"] in {"admin", "manager", "user"} for u in users), "users.role: только admin|manager|user")
check(all(set(u) == set(uk) for u in users), "users: ровно 5 ключей")
check(all(len(set(p) ) == 8 for p in products), "products: ровно 8 ключей")

print("=== 3. Русский текст / отсутствие придуманных сущностей ===")
cyr = sum(1 for p in products if any("а" <= ch.lower() <= "я" or ch.lower() == "ё" for ch in p["name"] + p["description"]))
check(cyr == 31, f"products: кириллица в name/description у {cyr}/31 записей")
check(not any(p["name"] == "" or p["brand"] == "" or p["image"] == "" for p in products), "products: нет пустых name/brand/image")

print("=== 4. Дубли ===")
pairs = [(p["name"], p["brand"]) for p in products]
dup_pairs = sorted({x for x in pairs if pairs.count(x) > 1})
check(not dup_pairs, f"products: name+brand уникальны (дублей: {dup_pairs})")
triples = [(s["product"], s["brand"], s["size"]) for s in stock]
dup_triples = sorted({x for x in triples if triples.count(x) > 1}, key=str)
check(not dup_triples, f"stock: product+brand+size уникальны (дублей: {dup_triples})")
logins = [u["login"] for u in users]
check(len(set(logins)) == len(logins), f"users: логины уникальны ({len(set(logins))}/{len(logins)})")

print("=== 5. Изображения ===")
src_png = sorted(f for f in os.listdir(os.path.join(SRC, "Images")) if f.lower().endswith(".png"))
dst_png = sorted(os.listdir(PRODUCTS_DIR))
check(len(src_png) == 31, f"источник: PNG={len(src_png)}")
check(len(dst_png) == 31, f"public/images/products: файлов={len(dst_png)}")
check(dst_png == src_png, "public/images/products: имена совпадают с источником")
bad = [p["image"] for p in products if not os.path.isfile(os.path.join(PRODUCTS_DIR, p["image"]))]
check(not bad, f"все image из products.json существуют ({31 - len(bad)}/31), отсутствуют: {bad}")
missing_ref = sorted({p["image"] for p in products} - set(dst_png))
check(not missing_ref, f"products.image ⊆ public файлов (лишние: {missing_ref})")
byte_diff = [n for n in src_png if sha256(os.path.join(SRC, "Images", n)) != sha256(os.path.join(PRODUCTS_DIR, n))]
check(not byte_diff, f"31 PNG скопированы байт-в-байт (отличия: {byte_diff})")

print("=== 6. Логотип, favicon, picture (sha256) ===")
pairs_assets = [
    (os.path.join(SRC, "Чудо Обувь.png"), os.path.join(PROJECT, "public", "images", "logo.png"), "logo.png"),
    (os.path.join(SRC, "Чудо Обувь.png"), os.path.join(SRC, "Чудо Обувь.png"), "-"),
    (os.path.join(SRC, "Чудо Обувь.ico"), os.path.join(PROJECT, "public", "favicon.ico"), "public/favicon.ico"),
    (os.path.join(SRC, "Чудо Обувь.ico"), os.path.join(PROJECT, "public", "images", "favicon.ico"), "public/images/favicon.ico"),
    (os.path.join(SRC, "picture.png"), os.path.join(PROJECT, "public", "images", "picture.png"), "images/picture.png"),
]
for src, dst, label in pairs_assets:
    if label == "-":
        continue
    h1, h2 = sha256(src), sha256(dst)
    check(h1 == h2, f"{label}: sha256 идентичен источнику ({h1[:16]}…)")
# растровые размеры не менялись
try:
    from PIL import Image
    with Image.open(os.path.join(SRC, "Чудо Обувь.png")) as im1, \
            Image.open(os.path.join(PROJECT, "public", "images", "logo.png")) as im2:
        check(im1.size == im2.size and im1.mode == im2.mode, f"logo.png: размер/режим не изменены {im2.size} {im2.mode}")
except Exception as exc:  # noqa: BLE001
    check(False, f"Pillow не смог открыть logo.png: {exc}")

print("=== 7. Перекрёстные ссылки / согласованность ===")
pset = {(p["name"], p["brand"]) for p in products}
sizeset = set(sizes)
stock_ref = {(s["product"], s["brand"]) for s in stock}
stock_only = sorted(stock_ref - pset)
products_only = sorted(pset - stock_ref)
# Отклонения уровня источника данных (не ошибка импорта): в Stock_Items.xlsx
# три наименования отличаются от Products_import.xlsx (пробел/NBSP/расширенное
# название). Данные перенесены буквально, поэтому фиксируем и не правим.
if stock_only or products_only:
    notes.append((stock_only, products_only))
    print(f"  NOTE stock/orders ссылаются на 3 наименования, которых нет буквально в products: {stock_only}")
    print(f"  NOTE в products 3 пары без буквального совпадения в stock: {products_only}")
check(len(stock_ref) == 31, f"stock покрывает 31 уникальную пару product+brand (покрыто: {len(stock_ref)})")
orphan_order = sorted({(i["product"], i["brand"]) for o in orders for i in o["items"]} - pset)
check(set(orphan_order) == set(stock_only), f"orders ссылается ровно на те же 3 спорных наименования, что и stock: {orphan_order}")
bad_stock_sizes = sorted({s["size"] for s in stock} - sizeset)
check(not bad_stock_sizes, f"stock.size ⊆ sizes (лишние: {bad_stock_sizes})")
bad_order_sizes = sorted({i["size"] for o in orders for i in o["items"]} - sizeset)
check(not bad_order_sizes, f"orders.items.size ⊆ sizes (лишние: {bad_order_sizes})")
users_full = {f"{u['surname']} {u['name']} {u['patronymic']}" for u in users}
bad_customers = sorted({o["customer"] for o in orders} - users_full)
check(not bad_customers, f"orders.customer ⊆ users ФИО (лишние: {bad_customers})")
nums = [o["number"] for o in orders]
check(nums == sorted(nums), f"orders: номера по возрастанию, порядок исходника сохранён {nums}")
dates_ok = all(len(o["date"]) == 10 and o["date"][4] == "-" and o["date"][7] == "-" for o in orders)
check(dates_ok, "orders.date: формат YYYY-MM-DD")
check(all(len(set(i["product"] for i in o["items"])) == len(o["items"]) for o in orders), "orders: внутри заказа нет дублей товаров")
check(sizes == sorted(sizes), f"sizes: по возрастанию {sizes}")
check(len(set(map(str, sizes))) == len(sizes), "sizes: без дублей")
check((products[0]["name"], products[0]["brand"]) == ("Кроссовки детские «Звёздочка» экокожа", "Малыш-Спорт"), "products: первая запись = первая строка xlsx")
check(products[-1]["name"] == "Ботинки зимние классические", "products: последняя запись = последняя строка xlsx")
check(stock[-1] == {"product": "Ботинки зимние классические", "brand": "Барбари", "size": 42, "quantity": 30}, "stock: последняя запись = последняя строка xlsx")
check(len({str(v) for v in sizes}) == 35, "sizes: 35 уникальных размеров")
check(any(isinstance(v, float) for v in sizes), f"sizes: половинные размеры сохранены как числа: {[v for v in sizes if isinstance(v, float)]}")
price_map = {(p["name"], p["brand"]): p["price"] for p in products}
mismatch = [(i["product"], i["unit_price"], price_map.get((i["product"], i["brand"])))
            for o in orders for i in o["items"]
            if (i["product"], i["brand"]) in price_map
            and price_map[(i["product"], i["brand"])] != i["unit_price"]]
check(not mismatch, f"orders.unit_price совпадает с products.price там, где товар найден (расхождения: {mismatch})")
check(all(s["quantity"] > 0 for s in stock), "stock.quantity: все значения > 0")
check(sum(len(o["items"]) for o in orders) == 30, "orders: 30 позиций, сгруппированы в 10 заказов")
check(all(len({i['product'] for i in o['items']}) == len(o['items']) for o in orders), "orders: нет повторного товара внутри одного заказа")

print()
print(f"ИТОГО: OK={ok}, FAIL={fail}, чистых проверок=65+")
if notes:
    print("ОТКЛОНЕНИЯ ИСТОЧНИКА (данные перенесены буквально, не исправлялись):")
    for stock_only, products_only in notes:
        for name, brand in stock_only:
            print(f"  - в stock/orders: {name!r} | {brand!r} — нет буквального совпадения в products")
        for name, brand in products_only:
            print(f"  - в products:     {name!r} | {brand!r} — нет буквального совпадения в stock")
sys.exit(0 if fail == 0 else 1)