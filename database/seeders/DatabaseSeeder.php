<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Импорт данных из ресурсов задания (database/data/*.json, получены из xlsx-файлов).
 */
class DatabaseSeeder extends Seeder
{
    /** @var array<string, int> */
    private array $categoryIds = [];

    /** @var array<string, Product> */
    private array $products = [];

    /** @var array<string, Size> */
    private array $sizes = [];

    public function run(): void
    {
        $this->seedSizes();
        $this->seedCategories();
        $this->seedProducts();
        $this->seedStock();
        $this->seedUsers();
        $this->seedOrders();
    }

    /**
     * @return array<int, mixed>
     */
    private function readJson(string $file): array
    {
        $path = database_path('data/'.$file);

        if (! is_file($path)) {
            throw new RuntimeException("Не найден файл ресурсов: {$path}");
        }

        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new RuntimeException("Файл ресурсов повреждён: {$path}");
        }

        return $data;
    }

    private function seedSizes(): void
    {
        foreach ($this->readJson('sizes.json') as $value) {
            $size = Size::firstOrCreate(['value' => (float) $value]);
            $this->sizes[(string) (float) $size->value] = $size;
        }
    }

    /**
     * Нормализация строковых значений из ресурсов: неразрывные пробелы, хвостовые
     * пробелы и повторные пробелы убираются, чтобы названия моделей сопоставлялись.
     */
    private function norm(?string $value): string
    {
        $value = str_replace(["\u{00A0}", "\u{202F}", "\u{2009}"], ' ', (string) $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return trim($value);
    }

    private function productKey(string $name, string $brand): string
    {
        return $this->norm($name).'|'.$this->norm($brand);
    }

    private function seedCategories(): void
    {
        foreach ($this->readJson('products.json') as $product) {
            $categoryName = $this->norm($product['category']);
            $subcategoryName = $this->norm($product['subcategory']);

            $category = Category::firstOrCreate([
                'name' => $categoryName,
                'parent_id' => null,
            ]);

            $subcategory = Category::firstOrCreate([
                'name' => $subcategoryName,
                'parent_id' => $category->id,
            ]);

            $this->categoryIds[$categoryName.'|'.$subcategoryName] = $subcategory->id;
        }
    }

    private function seedProducts(): void
    {
        foreach ($this->readJson('products.json') as $item) {
            $key = $this->productKey($item['name'], $item['brand']);

            if (isset($this->products[$key])) {
                continue;
            }

            $product = Product::create([
                'category_id' => $this->categoryIds[$this->norm($item['category']).'|'.$this->norm($item['subcategory'])],
                'name' => $this->norm($item['name']),
                'brand' => $this->norm($item['brand']),
                'description' => $item['description'],
                'composition' => $item['composition'],
                'price' => $item['price'],
                'image' => $item['image'],
            ]);

            $this->products[$key] = $product;
        }
    }

    /**
     * Поиск модели по названию и производству. В ресурсах встречаются варианты
     * названий (хвостовые пробелы, неразрывный пробел, уточняющий суффикс),
     * поэтому после нормализации допускается сопоставление по префиксу названия.
     */
    private function resolveProduct(string $name, string $brand): ?Product
    {
        $key = $this->productKey($name, $brand);

        if (isset($this->products[$key])) {
            return $this->products[$key];
        }

        $normalizedName = $this->norm($name);
        $normalizedBrand = $this->norm($brand);

        foreach ($this->products as $product) {
            if ($this->norm($product->brand) !== $normalizedBrand) {
                continue;
            }

            if (str_starts_with($this->norm($product->name), $normalizedName)) {
                $this->command?->warn(sprintf(
                    'Название «%s» сопоставлено с моделью «%s» (ресурс содержит вариант названия).',
                    $name,
                    $product->name,
                ));

                return $product;
            }
        }

        return null;
    }

    private function seedStock(): void
    {
        foreach ($this->readJson('stock.json') as $row) {
            $product = $this->resolveProduct($row['product'], $row['brand']);
            $size = $this->sizes[(string) (float) $row['size']] ?? null;

            if (! $product || ! $size) {
                $this->command?->warn(sprintf(
                    'Пропущена товарная позиция: «%s» (%s), размер %s.',
                    $row['product'],
                    $row['brand'],
                    $row['size'],
                ));

                continue;
            }

            ProductSize::updateOrCreate(
                ['product_id' => $product->id, 'size_id' => $size->id],
                ['quantity' => (int) $row['quantity']],
            );
        }
    }

    private function seedUsers(): void
    {
        foreach ($this->readJson('users.json') as $index => $item) {
            $login = trim($item['login']);

            User::updateOrCreate(
                ['login' => $login],
                [
                    'surname' => $item['surname'],
                    'name' => $item['name'],
                    'patronymic' => $item['patronymic'],
                    'email' => $login.'@chudo-obuv.ru',
                    'phone' => sprintf('+7 (900) %03d-%02d-%02d', 100 + $index, 10 + ($index % 50), 1 + ($index % 59)),
                    'password' => Hash::make('password'),
                    'role' => $item['role'],
                ],
            );
        }
    }

    private function seedOrders(): void
    {
        foreach ($this->readJson('orders.json') as $row) {
            $customer = trim($row['customer']);

            $user = User::all()->first(fn (User $candidate) => $candidate->full_name === $customer);

            $total = 0.0;

            foreach ($row['items'] as $item) {
                $total += (float) $item['unit_price'] * (int) $item['quantity'];
            }

            $order = Order::create([
                'user_id' => $user?->id,
                'customer_name' => $customer,
                'ordered_at' => $row['date'],
                'total' => $total,
                // Исторические заказы уже учтены в остатках, повторно склад не уменьшаем.
                'stock_applied' => false,
            ]);

            $products = [];

            foreach ($row['items'] as $item) {
                $product = $this->resolveProduct($item['product'], $item['brand']);

                if (! $product) {
                    throw new RuntimeException(sprintf(
                        'В заказе №%d указана модель, отсутствующая в каталоге: «%s» (%s).',
                        $row['number'],
                        $item['product'],
                        $item['brand'],
                    ));
                }

                $products[] = $product;
            }

            foreach ($row['items'] as $index => $item) {
                $product = $products[$index];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'brand' => $product->brand,
                    'size' => (float) $item['size'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }
        }
    }
}
