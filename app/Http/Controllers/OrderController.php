<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Cart;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly Cart $cart,
        private readonly OrderService $orders,
    ) {}

    /**
     * История заказов авторизованного пользователя.
     */
    public function index(): View
    {
        $orders = $this->userOrders()->paginate(10);

        return view('orders.index', ['orders' => $orders]);
    }

    public function show(Order $order): View
    {
        $this->authorizeOrder($order);
        $order->load('items');

        return view('orders.show', ['order' => $order]);
    }

    /**
     * Оформление заказа из корзины.
     */
    public function store(Request $request): RedirectResponse
    {
        $items = $this->cart->checkoutItems();

        if ($items === []) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Корзина пуста: добавьте товарные позиции в каталоге.']);
        }

        $user = $request->user();

        $order = $this->orders->create($user->full_name, now()->toDateString(), $items, $user->id);

        $this->cart->clear();

        return redirect()->route('orders.show', $order)
            ->with('status', 'Заказ №'.$order->id.' оформлен. Сумма: '.number_format((float) $order->total, 0, ',', ' ').' ₽.');
    }

    private function userOrders()
    {
        return Order::query()
            ->where('user_id', auth()->id())
            ->with('items')
            ->orderByDesc('ordered_at')
            ->orderByDesc('id');
    }

    private function authorizeOrder(Order $order): void
    {
        abort_unless((int) $order->user_id === (int) auth()->id(), 403, 'Просмотр чужого заказа запрещён.');
    }
}
