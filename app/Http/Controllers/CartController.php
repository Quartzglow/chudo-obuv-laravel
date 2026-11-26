<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Size;
use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly Cart $cart) {}

    public function index(): View
    {
        return view('cart.index', [
            'rows' => $this->cart->rows(),
            'total' => $this->cart->total(),
        ]);
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'size_id' => ['required', 'integer', 'exists:sizes,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ], [], [
            'size_id' => 'размер',
            'quantity' => 'количество',
        ]);

        $size = Size::findOrFail($data['size_id']);

        $this->cart->add($product, $size, (int) $data['quantity']);

        return back()->with('status', sprintf('В корзину добавлено: «%s», размер %d.', $product->name, $size->value));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'size_id' => ['required', 'integer', 'exists:sizes,id'],
            'quantity' => ['required', 'integer', 'min:0', 'max:999'],
        ], [], [
            'size_id' => 'размер',
            'quantity' => 'количество',
        ]);

        $size = Size::findOrFail($data['size_id']);

        $this->cart->setQuantity($product, $size, (int) $data['quantity']);

        return redirect()->route('cart.index')->with('status', 'Количество в корзине обновлено.');
    }

    public function remove(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'size_id' => ['required', 'integer', 'exists:sizes,id'],
        ]);

        $size = Size::findOrFail($data['size_id']);

        $this->cart->remove($product, $size);

        return redirect()->route('cart.index')->with('status', 'Товарная позиция удалена из корзины.');
    }

    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return redirect()->route('cart.index')->with('status', 'Корзина очищена.');
    }
}
