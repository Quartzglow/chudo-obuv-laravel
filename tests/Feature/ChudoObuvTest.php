<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Сквозные проверки требований технического задания.
 */
class ChudoObuvTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function userWithRole(string $role): User
    {
        return User::query()->where('role', $role)->firstOrFail();
    }

    private function stockItem(int $minQuantity = 5): ProductSize
    {
        return ProductSize::query()
            ->where('quantity', '>=', $minQuantity)
            ->with('product')
            ->firstOrFail();
    }

    public function test_ресурсы_импортированы_полностью(): void
    {
        $this->assertSame(31, Product::count());
        $this->assertSame(35, Size::count());
        $this->assertSame(92, ProductSize::count());
        $this->assertSame(20, User::count());
        $this->assertSame(10, Order::count());
        $this->assertSame(30, DB::table('order_items')->count());
        $this->assertSame(1274, (int) ProductSize::sum('quantity'));
        $this->assertSame(4, User::where('role', 'admin')->count());
        $this->assertSame(7, User::where('role', 'manager')->count());
        $this->assertSame(9, User::where('role', 'user')->count());
    }

    public function test_гость_видит_каталог_но_без_фильтрации_и_поиска(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Каталог товаров');
        $response->assertSee('Каталог доступен для просмотра без авторизации', false);
        $response->assertDontSee('name="category_id"', false);
        $response->assertDontSee('name="q"', false);
        $response->assertSee('images/logo.png', false);
        $response->assertSee('favicon.ico', false);
        $response->assertSee('<h1', false);
    }

    public function test_товары_с_остатком_до_трёх_подсвечиваются(): void
    {
        $lowStock = Product::with('sizes')->get()->filter(fn (Product $product) => $product->total_stock <= 3);
        $this->assertTrue($lowStock->count() >= 1, 'В ресурсах есть модели с остатком <= 3');

        $lowest = $lowStock->sortBy(fn (Product $product) => $product->total_stock)->first();

        $this->get('/catalog/'.$lowest->id)
            ->assertOk()
            ->assertSee('badge-low', false);

        // Поиск страницы каталога, содержащей подсвеченную карточку.
        $found = false;
        $lastPage = (int) ceil(Product::count() / 12);

        for ($page = 1; $page <= $lastPage; $page++) {
            $html = $this->get('/?page='.$page)->getContent();

            if (str_contains($html, 'is-low-stock')) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'В списке товаров подсвечивается цветом товар с остатком <= 3');
    }

    public function test_авторизованный_видит_фильтры_сортировку_и_поиск(): void
    {
        $user = $this->userWithRole('user');

        $this->actingAs($user)
            ->get('/catalog?q=кроссовки&sort=price_asc&in_stock=1')
            ->assertOk()
            ->assertSee('name="category_id"', false)
            ->assertSee('name="sort"', false)
            ->assertSee('name="q"', false)
            ->assertSee('Фильтрация, сортировка и поиск');
    }

    public function test_вход_по_логину_email_и_телефону(): void
    {
        foreach (['isivanov', 'isivanov@chudo-obuv.ru', '+7 (900) 100-10-01', '89001001001'] as $identifier) {
            $this->post('/login', ['identifier' => $identifier, 'password' => 'password'])
                ->assertRedirect('/');

            $this->assertAuthenticated();
            $this->post('/logout');
            $this->assertGuest();
        }
    }

    public function test_неверный_пароль_не_пускает_в_систему(): void
    {
        $this->from('/login')
            ->post('/login', ['identifier' => 'isivanov', 'password' => 'wrong-password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }

    public function test_пользователь_оформляет_заказ_и_остаток_уменьшается(): void
    {
        $user = $this->userWithRole('user');
        $item = $this->stockItem();
        $before = $item->quantity;

        $this->actingAs($user);

        $this->post(route('cart.add', $item->product_id), ['size_id' => $item->size_id, 'quantity' => 2])
            ->assertRedirect();

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee($item->product->name);

        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSee($item->product->name);

        $this->post(route('orders.store'))->assertRedirect();

        $order = Order::where('user_id', $user->id)->latest('id')->first();

        $this->assertNotNull($order);
        $this->assertSame($user->full_name, $order->customer_name);
        $this->assertSame(1, $order->items()->count());
        $this->assertEqualsWithDelta((float) $item->product->price * 2, (float) $order->total, 0.01);
        $this->assertSame($before - 2, ProductSize::find($item->id)->quantity);

        $this->get(route('cart.index'))->assertSee('Корзина пуста');
    }

    public function test_нельзя_заказать_больше_доступного_остатка(): void
    {
        $user = $this->userWithRole('user');
        $item = $this->stockItem();
        $ordersBefore = Order::where('user_id', $user->id)->count();

        $this->actingAs($user)
            ->from(route('catalog.show', $item->product_id))
            ->post(route('cart.add', $item->product_id), ['size_id' => $item->size_id, 'quantity' => 999])
            ->assertRedirect(route('catalog.show', $item->product_id))
            ->assertSessionHasErrors('quantity');

        $this->assertSame($ordersBefore, Order::where('user_id', $user->id)->count());
    }

    public function test_пользователь_видит_только_свои_заказы(): void
    {
        $user = $this->userWithRole('user');
        $other = User::where('role', 'user')->where('id', '!=', $user->id)->firstOrFail();

        $this->actingAs($user)->get(route('orders.index'))->assertOk();

        $order = Order::where('user_id', $other->id)->first();

        if ($order) {
            $this->actingAs($user)->get(route('orders.show', $order))->assertForbidden();
        } else {
            $this->assertTrue(true);
        }

        $this->actingAs($user)->get(route('manage.orders.index'))->assertForbidden();
    }

    public function test_профиль_редактирование_данных_и_смена_пароля(): void
    {
        $user = $this->userWithRole('user');

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'surname' => 'Иванова',
                'name' => 'Мария',
                'patronymic' => 'Петровна',
                'login' => 'mivanova',
                'email' => 'mivanova@chudo-obuv.ru',
                'phone' => '+7 (900) 555-44-33',
            ])
            ->assertRedirect(route('profile.show'));

        $user->refresh();
        $this->assertSame('Иванова', $user->surname);
        $this->assertSame('mivanova', $user->login);
        $this->assertSame('+7 (900) 555-44-33', $user->phone);

        $this->actingAs($user)
            ->patch(route('profile.password.update'), [
                'current_password' => 'password',
                'password' => 'new-secret-1',
                'password_confirmation' => 'new-secret-1',
            ])
            ->assertRedirect(route('profile.show'));

        $this->assertTrue(Hash::check('new-secret-1', $user->fresh()->password));
    }

    public function test_восстановление_пароля_по_ссылке(): void
    {
        $user = $this->userWithRole('manager');

        $this->post(route('password.email'), ['identifier' => $user->login])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('reset_link');

        $token = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
        $this->assertNotEmpty($token);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'restored-pass-1',
            'password_confirmation' => 'restored-pass-1',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('restored-pass-1', $user->fresh()->password));

        $this->post('/login', ['identifier' => $user->login, 'password' => 'restored-pass-1'])
            ->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_менеджер_просматривает_добавляет_и_удаляет_заказы(): void
    {
        $manager = $this->userWithRole('manager');
        $item = $this->stockItem();
        $before = $item->quantity;

        $this->actingAs($manager)->get(route('manage.orders.index'))->assertOk()->assertSee('Управление заказами');
        $this->actingAs($manager)->get(route('manage.orders.show', Order::first()))->assertOk()->assertSee('Состав заказа');
        $this->actingAs($manager)->get(route('manage.orders.create'))->assertOk()->assertSee('Новый заказ');

        $this->actingAs($manager)->post(route('manage.orders.store'), [
            'customer_name' => 'Тестовый Клиент',
            'ordered_at' => '2026-05-01',
            'items' => [
                ['product_id' => $item->product_id, 'size' => (float) $item->size->value, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $order = Order::where('customer_name', 'Тестовый Клиент')->firstOrFail();
        $this->assertSame(1, $order->items()->count());
        $this->assertSame($before - 2, ProductSize::find($item->id)->quantity);

        $this->actingAs($manager)->delete(route('manage.orders.destroy', $order))->assertRedirect(route('manage.orders.index'));
        $this->assertNull(Order::find($order->id));
        $this->assertSame($before, ProductSize::find($item->id)->quantity);
    }

    public function test_менеджер_не_может_редактировать_заказ(): void
    {
        $manager = $this->userWithRole('manager');
        $order = Order::firstOrFail();

        $this->actingAs($manager)->get(route('manage.orders.edit', $order))->assertForbidden();
        $this->actingAs($manager)->put(route('manage.orders.update', $order), [])->assertForbidden();
    }

    public function test_администратор_редактирует_заказ(): void
    {
        $admin = $this->userWithRole('admin');
        $item = $this->stockItem();
        $before = $item->quantity;

        $this->actingAs($admin)->post(route('manage.orders.store'), [
            'customer_name' => 'Клиент Для Правки',
            'ordered_at' => '2026-05-02',
            'items' => [
                ['product_id' => $item->product_id, 'size' => (float) $item->size->value, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $order = Order::where('customer_name', 'Клиент Для Правки')->firstOrFail();

        $this->actingAs($admin)->get(route('manage.orders.edit', $order))->assertOk()->assertSee('Редактирование заказа');

        $other = ProductSize::where('quantity', '>=', 4)->where('id', '!=', $item->id)->with('product')->firstOrFail();

        $this->actingAs($admin)->put(route('manage.orders.update', $order), [
            'customer_name' => 'Клиент Для Правки',
            'ordered_at' => '2026-05-03',
            'items' => [
                ['product_id' => $other->product_id, 'size' => (float) $other->size->value, 'quantity' => 3],
            ],
        ])->assertRedirect(route('manage.orders.show', $order));

        $order->refresh();
        $this->assertSame('2026-05-03', $order->ordered_at->format('Y-m-d'));
        $this->assertSame(1, $order->items()->count());
        $this->assertSame((int) $other->size->value, (int) $order->items()->first()->size);
        $this->assertSame($before, ProductSize::find($item->id)->quantity);
        $this->assertSame($other->quantity - 3, ProductSize::find($other->id)->quantity);
    }

    public function test_нельзя_создать_заказ_сверх_остатка(): void
    {
        $manager = $this->userWithRole('manager');
        $item = $this->stockItem();

        $this->actingAs($manager)
            ->from(route('manage.orders.create'))
            ->post(route('manage.orders.store'), [
                'customer_name' => 'Жадный Клиент',
                'ordered_at' => '2026-05-01',
                'items' => [
                    ['product_id' => $item->product_id, 'size' => (float) $item->size->value, 'quantity' => 9999],
                ],
            ])
            ->assertSessionHasErrors();

        $this->assertSame(0, Order::where('customer_name', 'Жадный Клиент')->count());
    }

    public function test_поиск_регистронезависим_для_кириллицы(): void
    {
        $user = $this->userWithRole('user');

        $expectedCatalog = Product::all()->filter(
            fn (Product $product) => mb_strpos(mb_strtolower($product->name.' '.$product->brand.' '.$product->description), 'кроссовки') !== false
        )->count();

        $this->assertGreaterThan(0, $expectedCatalog);

        $response = $this->actingAs($user)->get('/catalog?q=кроссовки');
        $response->assertOk();
        $this->assertSame($expectedCatalog, $response->viewData('products')->total());

        $responseUpper = $this->actingAs($user)->get('/catalog?q=КРОССОВКИ');
        $this->assertSame($expectedCatalog, $responseUpper->viewData('products')->total());

        $manager = $this->userWithRole('manager');
        $expectedOrders = Order::all()->filter(
            fn (Order $order) => mb_strpos(mb_strtolower($order->customer_name), 'сидорова') !== false
        )->count();

        $ordersResponse = $this->actingAs($manager)->get('/manage/orders?q=сидорова');
        $ordersResponse->assertOk();
        $this->assertSame($expectedOrders, $ordersResponse->viewData('orders')->total());
    }

    public function test_правка_исторического_заказа_не_меняет_остатки(): void
    {
        $admin = $this->userWithRole('admin');
        $order = Order::where('stock_applied', false)->with('items')->firstOrFail();
        $stockBefore = (int) ProductSize::sum('quantity');

        $items = $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'size' => (float) $item->size,
            'quantity' => (int) $item->quantity,
        ])->all();

        $this->actingAs($admin)->put(route('manage.orders.update', $order), [
            'customer_name' => $order->customer_name,
            'ordered_at' => $order->ordered_at->format('Y-m-d'),
            'items' => $items,
        ])->assertRedirect(route('manage.orders.show', $order));

        $this->assertSame($stockBefore, (int) ProductSize::sum('quantity'), 'Правка исторического заказа не списывает остаток');

        $order->refresh();
        $this->assertFalse((bool) $order->stock_applied);

        $this->actingAs($admin)->delete(route('manage.orders.destroy', $order))->assertRedirect(route('manage.orders.index'));
        $this->assertSame($stockBefore, (int) ProductSize::sum('quantity'));
    }

    public function test_гость_и_пользователь_не_имеют_доступа_к_управлению_заказами(): void
    {
        $this->get(route('manage.orders.index'))->assertRedirect(route('login'));

        $user = $this->userWithRole('user');
        $this->actingAs($user)->get(route('manage.orders.index'))->assertForbidden();
        $this->actingAs($user)->get(route('manage.orders.create'))->assertForbidden();
    }

    public function test_все_экранные_формы_имеют_заголовок(): void
    {
        $user = $this->userWithRole('user');
        $manager = $this->userWithRole('manager');
        $admin = $this->userWithRole('admin');

        $guestPaths = ['/', '/catalog', '/catalog/1', '/login', '/password/forgot'];
        foreach ($guestPaths as $path) {
            $this->get($path)->assertOk()->assertSee('<h1', false);
        }

        $this->actingAs($user);
        $userOrder = Order::where('user_id', $user->id)->firstOrFail();
        $userPaths = ['/profile', '/profile/edit', '/profile/password', '/profile/orders', '/profile/orders/'.$userOrder->id, '/cart'];
        foreach ($userPaths as $path) {
            $this->get($path)->assertOk()->assertSee('<h1', false);
        }

        $this->actingAs($manager);
        $order = Order::firstOrFail();
        foreach (['/manage/orders', '/manage/orders/create', '/manage/orders/'.$order->id] as $path) {
            $this->get($path)->assertOk()->assertSee('<h1', false);
        }

        $this->actingAs($admin);
        $this->get('/manage/orders/'.$order->id.'/edit')->assertOk()->assertSee('<h1', false);
    }

    public function test_оформление_по_руководству_стиля(): void
    {
        $css = strtolower((string) file_get_contents(public_path('css/app.css')));

        $this->assertStringContainsString('#ffffff', $css, 'Основной фон — белый');
        $this->assertStringContainsString('#d2f6e7', $css, 'Дополнительный фон — #D2F6E7');
        $this->assertStringContainsString('#70b2af', $css, 'Акцентирование — #70B2AF');
        $this->assertStringContainsString('#ff8080', $css, 'Подсветка остатка <= 3 — #ff8080');
        $this->assertStringContainsString('calibri', $css, 'Шрифт — Calibri');

        $this->assertFileExists(public_path('images/logo.png'));
        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertDirectoryExists(public_path('images/products'));
        $this->assertGreaterThan(10000, (int) filesize(public_path('images/logo.png')));

        // Логотип не искажается: пропорции сохраняются (квадратное изображение 1024x1024).
        $size = getimagesize(public_path('images/logo.png'));
        $this->assertSame(1024, $size[0]);
        $this->assertSame(1024, $size[1]);

        $this->get('/')->assertSee('images/logo.png', false)->assertSee('favicon.ico', false);
    }

    public function test_исторические_заказы_не_повторно_списывают_остатки(): void
    {
        $this->assertSame(1274, (int) ProductSize::sum('quantity'));
        $this->assertTrue(Order::query()->where('stock_applied', false)->exists());
    }
}
