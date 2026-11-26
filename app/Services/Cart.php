<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

/**
 * Корзина покупателя хранится в сессии: ключ "идентификатор товара:идентификатор размера".
 */
class Cart
{
    private const SESSION_KEY = 'cart';

    /**
     * @return array<string, int>
     */
    public function raw(): array
    {
        $items = Session::get(self::SESSION_KEY, []);

        return is_array($items) ? $items : [];
    }

    /**
     * @param  array<string, int>  $items
     */
    public function put(array $items): void
    {
        Session::put(self::SESSION_KEY, $items);
    }

    public function available(Product $product, Size $size): int
    {
        return (int) (ProductSize::where('product_id', $product->id)
            ->where('size_id', $size->id)
            ->value('quantity') ?? 0);
    }

    public function add(Product $product, Size $size, int $quantity): void
    {
        $items = $this->raw();
        $key = $this->key($product, $size);
        $current = (int) ($items[$key] ?? 0);
        $available = $this->available($product, $size);
        $newQuantity = $current + $quantity;

        if ($newQuantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => sprintf(
                    'Для модели «%s» размера %s доступно только %d шт.',
                    $product->name,
                    Size::formatValue($size->value),
                    $available,
                ),
            ]);
        }

        if ($newQuantity <= 0) {
            unset($items[$key]);
        } else {
            $items[$key] = $newQuantity;
        }

        $this->put($items);
    }

    public function setQuantity(Product $product, Size $size, int $quantity): void
    {
        $items = $this->raw();
        $key = $this->key($product, $size);
        $available = $this->available($product, $size);

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => sprintf(
                    'Для модели «%s» размера %s доступно только %d шт.',
                    $product->name,
                    Size::formatValue($size->value),
                    $available,
                ),
            ]);
        }

        if ($quantity <= 0) {
            unset($items[$key]);
        } else {
            $items[$key] = $quantity;
        }

        $this->put($items);
    }

    public function remove(Product $product, Size $size): void
    {
        $items = $this->raw();
        unset($items[$this->key($product, $size)]);
        $this->put($items);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return (int) array_sum($this->raw());
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    /**
     * Подробные строки корзины с актуальными остатками.
     *
     * @return array<int, array{product: Product, size: Size, quantity: int, available: int, line_total: float}>
     */
    public function rows(): array
    {
        $rows = [];
        $items = $this->raw();
        $changed = false;

        foreach ($items as $key => $quantity) {
            [$productId, $sizeId] = array_pad(explode(':', (string) $key), 2, null);

            $product = Product::with('sizes')->find($productId);
            $size = Size::find($sizeId);

            if (! $product || ! $size) {
                unset($items[$key]);
                $changed = true;

                continue;
            }

            $available = $this->available($product, $size);
            $quantity = (int) $quantity;

            if ($available <= 0) {
                unset($items[$key]);
                $changed = true;

                continue;
            }

            if ($quantity > $available) {
                $quantity = $available;
                $items[$key] = $available;
                $changed = true;
            }

            $rows[] = [
                'product' => $product,
                'size' => $size,
                'quantity' => $quantity,
                'available' => $available,
                'line_total' => (float) $product->price * $quantity,
            ];
        }

        if ($changed) {
            $this->put($items);
        }

        return $rows;
    }

    public function total(): float
    {
        return array_sum(array_map(fn (array $row) => $row['line_total'], $this->rows()));
    }

    /**
     * Позиции для оформления заказа.
     *
     * @return array<int, array{product_id: int, size: int, quantity: int}>
     */
    public function checkoutItems(): array
    {
        return array_map(fn (array $row) => [
            'product_id' => $row['product']->id,
            'size' => (float) $row['size']->value,
            'quantity' => (int) $row['quantity'],
        ], $this->rows());
    }

    private function key(Product $product, Size $size): string
    {
        return $product->id.':'.$size->id;
    }
}
