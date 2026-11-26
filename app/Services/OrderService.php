<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Оформление, изменение и удаление заказов с учётом доступного для заказа количества.
 */
class OrderService
{
    /**
     * @param  array<int, array{product_id: int, size: float|int|string, quantity: int}>  $items
     */
    public function create(string $customerName, string $orderedAt, array $items, ?int $userId = null): Order
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'В заказе должна быть хотя бы одна товарная позиция.']);
        }

        return DB::transaction(function () use ($customerName, $orderedAt, $items, $userId) {
            $order = Order::create([
                'user_id' => $userId,
                'customer_name' => $customerName,
                'ordered_at' => $orderedAt,
                'total' => 0,
                'stock_applied' => true,
            ]);

            $total = 0.0;

            foreach ($items as $item) {
                $product = Product::find($item['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages(['items' => 'Товар не найден.']);
                }

                $size = (float) $item['size'];
                $row = $this->stockRow($product->id, $size, lock: true);

                if (! $row) {
                    throw ValidationException::withMessages([
                        'items' => sprintf('Размер %s недоступен для модели «%s».', Size::formatValue($size), $product->name),
                    ]);
                }

                if ($row->quantity < (int) $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => sprintf(
                            'Для модели «%s» размера %s доступно только %d шт.',
                            $product->name,
                            Size::formatValue($size),
                            $row->quantity,
                        ),
                    ]);
                }

                $row->decrement('quantity', (int) $item['quantity']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'brand' => $product->brand,
                    'size' => $size,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => $product->price,
                ]);

                $total += (float) $product->price * (int) $item['quantity'];
            }

            $order->update(['total' => $total]);

            return $order;
        });
    }

    /**
     * Полное обновление заказа: состав и данные клиента.
     *
     * @param  array<int, array{product_id: int, size: float|int|string, quantity: int}>  $items
     */
    public function update(Order $order, string $customerName, string $orderedAt, array $items): Order
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'В заказе должна быть хотя бы одна товарная позиция.']);
        }

        return DB::transaction(function () use ($order, $customerName, $orderedAt, $items) {
            $order->refresh();

            // Исторические (импортированные) заказы уже учтены в текущем остатке,
            // поэтому их редактирование не должно повторно менять доступное количество.
            $affectsStock = (bool) $order->stock_applied;

            if ($affectsStock) {
                $this->restoreStock($order);
            }

            $order->items()->delete();
            $order->update([
                'customer_name' => $customerName,
                'ordered_at' => $orderedAt,
                'stock_applied' => $affectsStock,
                'total' => 0,
            ]);

            $total = 0.0;

            foreach ($items as $item) {
                $product = Product::find($item['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages(['items' => 'Товар не найден.']);
                }

                $size = (float) $item['size'];

                if ($affectsStock) {
                    $row = $this->stockRow($product->id, $size, lock: true);

                    if (! $row || $row->quantity < (int) $item['quantity']) {
                        throw ValidationException::withMessages([
                            'items' => sprintf(
                                'Для модели «%s» размера %s доступно только %d шт.',
                                $product->name,
                                Size::formatValue($size),
                                $row?->quantity ?? 0,
                            ),
                        ]);
                    }

                    $row->decrement('quantity', (int) $item['quantity']);
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'brand' => $product->brand,
                    'size' => $size,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => $product->price,
                ]);

                $total += (float) $product->price * (int) $item['quantity'];
            }

            $order->update(['total' => $total]);

            return $order;
        });
    }

    /**
     * Удаление заказа с возвратом товарных позиций в доступный остаток.
     */
    public function delete(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->refresh();

            if ($order->stock_applied) {
                $this->restoreStock($order);
            }

            $order->delete();
        });
    }

    private function restoreStock(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $row = $this->stockRow($item->product_id, (float) $item->size);

            if ($row) {
                $row->increment('quantity', (int) $item->quantity);
            }
        }
    }

    public function availableQuantity(int $productId, float|int|string $sizeValue): int
    {
        return (int) ($this->stockRow($productId, (float) $sizeValue)?->quantity ?? 0);
    }

    public function stockRow(int $productId, float|int|string $sizeValue, bool $lock = false): ?ProductSize
    {
        $sizeId = Size::where('value', (float) $sizeValue)->value('id');

        if (! $sizeId) {
            return null;
        }

        $query = ProductSize::where('product_id', $productId)->where('size_id', $sizeId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }
}
