<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Управление заказами: доступно менеджеру и администратору.
 * Редактирование заказа доступно только администратору.
 */
class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'date_from', 'date_to', 'sort']);

        $orders = Order::query()
            ->with('items')
            ->withCount('items')
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = '%'.Str::lower(trim((string) $filters['q'])).'%';
                $query->where(function ($query) use ($filters, $term) {
                    $query->where('customer_search', 'like', $term)
                        ->orWhere('customer_name', 'like', $term);

                    if (ctype_digit(trim((string) $filters['q']))) {
                        $query->orWhere('id', (int) $filters['q']);
                    }
                });
            })
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('ordered_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('ordered_at', '<=', $filters['date_to']));

        match ($filters['sort'] ?? 'date_desc') {
            'date_asc' => $orders->orderBy('ordered_at')->orderBy('id'),
            'total_desc' => $orders->orderByDesc('total'),
            'total_asc' => $orders->orderBy('total'),
            default => $orders->orderByDesc('ordered_at')->orderByDesc('id'),
        };

        return view('manage.orders.index', [
            'orders' => $orders->paginate(10)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'user']);

        return view('manage.orders.show', ['order' => $order]);
    }

    public function create(): View
    {
        return view('manage.orders.form', [
            'order' => null,
            'products' => $this->productsPayload(),
            'customers' => $this->customerNames(),
            'initialItems' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateOrder($request);
        $order = $this->orders->create($data['customer_name'], $data['ordered_at'], $this->mapItems($data['items']));

        return redirect()->route('manage.orders.show', $order)
            ->with('status', 'Заказ №'.$order->id.' добавлен.');
    }

    /**
     * Редактирование заказа — только администратор.
     */
    public function edit(Order $order): View
    {
        $order->load('items');

        return view('manage.orders.form', [
            'order' => $order,
            'products' => $this->productsPayload(),
            'customers' => $this->customerNames(),
            'initialItems' => $order->items->map(fn (OrderItem $item) => [
                'product_id' => $item->product_id,
                'size' => (float) $item->size,
                'quantity' => (int) $item->quantity,
                'name' => $item->product_name,
                'brand' => $item->brand,
                'price' => (float) $item->unit_price,
            ])->values()->all(),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $this->validateOrder($request);
        $order = $this->orders->update($order, $data['customer_name'], $data['ordered_at'], $this->mapItems($data['items']));

        return redirect()->route('manage.orders.show', $order)->with('status', 'Заказ №'.$order->id.' сохранён.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $id = $order->id;
        $this->orders->delete($order);

        return redirect()->route('manage.orders.index')->with('status', 'Заказ №'.$id.' удалён, товарные позиции возвращены в каталог.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateOrder(Request $request): array
    {
        return $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'ordered_at' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.size' => ['required', 'numeric', 'min:1', 'max:60'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ], [
            'items.required' => 'Добавьте хотя бы одну товарную позицию.',
            'items.min' => 'Добавьте хотя бы одну товарную позицию.',
        ], [
            'customer_name' => 'ФИО клиента',
            'ordered_at' => 'дата заказа',
            'items' => 'товарные позиции',
            'items.*.product_id' => 'модель',
            'items.*.size' => 'размер',
            'items.*.quantity' => 'количество',
        ]);
    }

    /**
     * @param  array<int, array{product_id: int|string, size: mixed, quantity: int|string}>  $items
     * @return array<int, array{product_id: int, size: float, quantity: int}>
     */
    private function mapItems(array $items): array
    {
        return array_values(array_map(fn (array $item) => [
            'product_id' => (int) $item['product_id'],
            'size' => (float) $item['size'],
            'quantity' => (int) $item['quantity'],
        ], $items));
    }

    /**
     * Данные для формы заказа: модели с размерами и остатками.
     *
     * @return array<int, array<string, mixed>>
     */
    private function productsPayload(): array
    {
        return Product::query()
            ->with('sizes')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand,
                'price' => (float) $product->price,
                'sizes' => $product->sizes->map(fn ($size) => [
                    'value' => (float) $size->value,
                    'label' => Size::formatValue($size->value),
                    'quantity' => (int) $size->pivot->quantity,
                ])->values()->all(),
            ])->values()->all();
    }

    /**
     * Подсказки ФИО клиентов: зарегистрированные пользователи и клиенты из заказов.
     *
     * @return array<int, string>
     */
    private function customerNames(): array
    {
        $names = User::all()->map(fn (User $user) => $user->full_name)->all();
        $names = array_merge($names, Order::query()->distinct()->pluck('customer_name')->all());
        $names = array_values(array_unique(array_filter(array_map('trim', $names))));
        sort($names);

        return $names;
    }
}
