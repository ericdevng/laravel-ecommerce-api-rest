<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use Tests\TestCase;

class EcommerceApiTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(array $overrides = []): Product
    {
        $category = Category::create(['name' => 'Electronics']);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Laptop',
            'price' => 999.99,
            'stock' => 10,
            'is_active' => true,
        ], $overrides));
    }

    public function test_products_can_be_listed(): void
    {
        $product = $this->createProduct();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.name', 'Laptop');
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $electronics = Category::create(['name' => 'Electronics']);
        $clothing = Category::create(['name' => 'Clothing']);

        Product::create([
            'category_id' => $electronics->id,
            'name' => 'Laptop',
            'price' => 999.99,
            'stock' => 10,
        ]);
        Product::create([
            'category_id' => $clothing->id,
            'name' => 'T-Shirt',
            'price' => 19.99,
            'stock' => 20,
        ]);

        $this->getJson('/api/products?category='.$electronics->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laptop');
    }

    public function test_products_filtered_by_unknown_category_returns_404(): void
    {
        $this->getJson('/api/products?category=999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Category not found.');
    }

    public function test_categories_can_be_listed(): void
    {
        $category = Category::create(['name' => 'Electronics']);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $category->id);
    }

    public function test_category_can_be_shown(): void
    {
        $category = Category::create(['name' => 'Electronics']);

        $this->getJson('/api/categories/'.$category->id)
            ->assertOk()
            ->assertJsonPath('name', 'Electronics');
    }

    public function test_inactive_products_are_not_listed(): void
    {
        $this->createProduct(['is_active' => false]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_product_can_be_shown(): void
    {
        $product = $this->createProduct();

        $this->getJson('/api/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('name', 'Laptop');
    }

    public function test_register_returns_user_and_token(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Ana',
            'email' => 'ana@test.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])
            ->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
    }

    public function test_guest_cannot_access_cart(): void
    {
        $this->getJson('/api/cart')->assertUnauthorized();
    }

    public function test_full_purchase_flow(): void
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        $headers = ['Authorization' => "Bearer $token"];

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ], $headers)->assertCreated();

        $this->getJson('/api/cart', $headers)
            ->assertOk()
            ->assertJsonPath('items.0.quantity', 2);

        $this->postJson('/api/orders', [], $headers)
            ->assertCreated()
            ->assertJsonPath('total', '1999.98')
            ->assertJsonPath('items.0.quantity', 2);

        $this->getJson('/api/cart', $headers)
            ->assertOk()
            ->assertJsonCount(0, 'items');

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'total' => '1999.98']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 8]);
    }

    public function test_order_is_created_with_pending_status(): void
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $headers = $this->authHeaders($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ], $headers)->assertCreated();

        $this->postJson('/api/orders', [], $headers)
            ->assertCreated()
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('orders', ['status' => 'pending']);
    }

    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.auth('api')->login($user)];
    }

    public function test_cart_item_quantity_can_be_updated(): void
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $headers = $this->authHeaders($user);

        $itemId = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ], $headers)->assertCreated()->json('id');

        $this->patchJson('/api/cart/items/'.$itemId, ['quantity' => 3], $headers)
            ->assertOk()
            ->assertJsonPath('quantity', 3);

        $this->assertDatabaseHas('cart_items', ['id' => $itemId, 'quantity' => 3]);
    }

    public function test_cart_item_quantity_can_be_increased(): void
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $headers = $this->authHeaders($user);

        $itemId = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ], $headers)->assertCreated()->json('id');

        $this->patchJson('/api/cart/items/'.$itemId, ['quantity' => 7], $headers)
            ->assertOk()
            ->assertJsonPath('quantity', 7);
    }

    public function test_cart_item_quantity_cannot_exceed_stock(): void
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $headers = $this->authHeaders($user);

        $itemId = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ], $headers)->assertCreated()->json('id');

        $this->patchJson('/api/cart/items/'.$itemId, ['quantity' => 11], $headers)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Insufficient stock.');
    }

    public function test_cart_item_cannot_be_updated_with_zero_quantity(): void
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $headers = $this->authHeaders($user);

        $itemId = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ], $headers)->assertCreated()->json('id');

        $this->patchJson('/api/cart/items/'.$itemId, ['quantity' => 0], $headers)
            ->assertUnprocessable();
    }

    public function test_inactive_product_cannot_be_added_to_cart(): void
    {
        $product = $this->createProduct(['is_active' => false]);
        $user = User::factory()->create();
        $headers = $this->authHeaders($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ], $headers)
            ->assertNotFound()
            ->assertJsonPath('message', 'Product not found.');
    }

    public function test_user_can_list_their_orders(): void
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $headers = $this->authHeaders($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ], $headers)->assertCreated();

        $this->postJson('/api/orders', [], $headers)->assertCreated();

        $this->getJson('/api/orders', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.items.0.quantity', 2);
    }

    public function test_user_only_sees_their_own_orders(): void
    {
        $product = $this->createProduct();
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ], $this->authHeaders($first))->assertCreated();
        $this->postJson('/api/orders', [], $this->authHeaders($first))->assertCreated();

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ], $this->authHeaders($second))->assertCreated();
        $this->postJson('/api/orders', [], $this->authHeaders($second))->assertCreated();

        $this->getJson('/api/orders', $this->authHeaders($first))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $product = $this->createProduct();
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ], $this->authHeaders($owner))->assertCreated();

        $orderId = $this->postJson('/api/orders', [], $this->authHeaders($owner))
            ->assertCreated()
            ->json('id');

        $this->getJson('/api/orders/'.$orderId, $this->authHeaders($other))
            ->assertNotFound()
            ->assertJsonPath('message', 'Order not found.');
    }

    public function test_nonexistent_order_returns_404(): void
    {
        $user = User::factory()->create();

        $this->getJson('/api/orders/999', $this->authHeaders($user))
            ->assertNotFound()
            ->assertJsonPath('message', 'Order not found.');
    }

    public function test_guest_cannot_access_orders(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized();
    }

    public function test_login_is_rate_limited_after_failed_attempts(): void
    {
        $user = User::factory()->create();

        // 5 intentos con credenciales incorrectas → 401
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        // El 6º intento dentro del mismo minuto → 429 Too Many Requests
        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_add_item_accumulates_quantity(): void
    {
        $product = $this->createProduct(['stock' => 100]);
        $user = User::factory()->create();
        $headers = $this->authHeaders($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ], $headers)->assertCreated();

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ], $headers)->assertCreated();

        $this->getJson('/api/cart', $headers)
            ->assertOk()
            ->assertJsonPath('items.0.quantity', 5);
    }

    /**
     * Demuestra el problema que estamos resolviendo: si dos sesiones hacen
     * "leer → sumar → escribir" sin bloquear, se pierde una actualización
     * (quedan 7 en lugar de 9). Esto es lo que el lockForUpdate previene.
     */
    public function test_concurrent_read_modify_write_loses_an_update(): void
    {
        $itemId = $this->setupRawCartItem();

        $sessionA = $this->newPdo();
        $sessionB = $this->newPdo();

        $sessionA->beginTransaction();
        $sessionB->beginTransaction();

        // Ambas "leen" la cantidad ANTES de que la otra escriba.
        $qtyA = (int) $sessionA->query("SELECT quantity FROM cart_items WHERE id = {$itemId}")->fetchColumn();
        $qtyB = (int) $sessionB->query("SELECT quantity FROM cart_items WHERE id = {$itemId}")->fetchColumn();

        // A escribe su 7 y hace commit (en la vida real esto pasa en paralelo).
        $sessionA->exec("UPDATE cart_items SET quantity = {$qtyA} + 2 WHERE id = {$itemId}");
        $sessionA->commit();

        // B escribe su 7 calculado sobre una lectura YA desactualizada.
        $sessionB->exec("UPDATE cart_items SET quantity = {$qtyB} + 2 WHERE id = {$itemId}");
        $sessionB->commit();

        // Pérdida de actualización: 7 en lugar de 9.
        $final = (int) $this->newPdo()
            ->query("SELECT quantity FROM cart_items WHERE id = {$itemId}")
            ->fetchColumn();

        $this->assertSame(7, $final);
    }

    /**
     * Demuestra que el lockForUpdate SÍ bloquea a la segunda sesión: mientras
     * la sesión A tiene bloqueada la fila del producto, la sesión B espera y
     * termina en timeout. Eso es lo que serializa las operaciones.
     */
    public function test_for_update_lock_blocks_concurrent_session(): void
    {
        $itemId = $this->setupRawCartItem();
        $productId = (int) $this->newPdo()
            ->query("SELECT product_id FROM cart_items WHERE id = {$itemId}")
            ->fetchColumn();

        $sessionA = $this->newPdo();
        $sessionB = $this->newPdo();

        $sessionA->beginTransaction();
        $sessionA->exec("SELECT * FROM products WHERE id = {$productId} FOR UPDATE");

        $sessionB->beginTransaction();
        $sessionB->exec('SET statement_timeout = 1000');

        try {
            // Debe quedarse esperando hasta agotar el timeout.
            $sessionB->exec("SELECT * FROM products WHERE id = {$productId} FOR UPDATE");
            $sessionA->rollBack();
            $this->fail('La segunda sesión debió quedar bloqueada por el lock.');
        } catch (PDOException $e) {
            $sessionA->rollBack();
            // 57014 = "query_canceled" (statement timeout). Comprobar el
            // código SQLSTATE es robusto aunque PostgreSQL esté en otro idioma.
            $this->assertSame('57014', $e->getCode());
        }
    }

    /**
     * Abre una conexión PDO nueva (una "sesión" distinta de PostgreSQL).
     */
    private function newPdo(): PDO
    {
        $config = config('database.connections.pgsql');
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $config['host'],
            $config['port'],
            $config['database']
        );

        return new PDO($dsn, $config['username'], $config['password']);
    }

    /**
     * Crea categoría, producto, usuario, carrito e item (quantity=5) con SQL
     * crudo y commit, para que no dependan de la transacción del test.
     * El email es único por corrida para que no choque si el tearDown no corre.
     */
    private function setupRawCartItem(): int
    {
        $pdo = $this->newPdo();
        $email = 'demo-'.uniqid().'@test.com';

        $pdo->exec("INSERT INTO categories (name) VALUES ('Demo')");
        $categoryId = $pdo->lastInsertId();

        $pdo->exec("INSERT INTO products (category_id, name, price, stock, is_active, created_at, updated_at) VALUES ({$categoryId}, 'Demo', 29.99, 100, true, NOW(), NOW())");
        $productId = $pdo->lastInsertId();

        $pdo->exec("INSERT INTO users (name, email, password, created_at, updated_at) VALUES ('Demo', '{$email}', 'secret123', NOW(), NOW())");
        $userId = $pdo->lastInsertId();

        $pdo->exec("INSERT INTO cart (user_id, created_at, updated_at) VALUES ({$userId}, NOW(), NOW())");
        $cartId = $pdo->lastInsertId();

        $pdo->exec("INSERT INTO cart_items (cart_id, product_id, quantity, unit_price, created_at, updated_at) VALUES ({$cartId}, {$productId}, 5, 29.99, NOW(), NOW())");
        $itemId = $pdo->lastInsertId();

        return $itemId;
    }

    protected function tearDown(): void
    {
        // Estos tests insertan con commit (sesiones PDO propias), así que
        // RefreshDatabase no los revierte. Limpiamos aquí para no contaminar
        // la BD de tests entre corridas.
        $pdo = $this->newPdo();
        $pdo->exec("DELETE FROM cart_items WHERE product_id IN (SELECT id FROM products WHERE name='Demo')");
        $pdo->exec("DELETE FROM cart WHERE user_id IN (SELECT id FROM users WHERE name='Demo')");
        $pdo->exec("DELETE FROM users WHERE name='Demo'");
        $pdo->exec("DELETE FROM products WHERE name='Demo'");
        $pdo->exec("DELETE FROM categories WHERE name='Demo'");

        parent::tearDown();
    }

    public function test_product_show_is_served_from_cache(): void
    {
        $product = $this->createProduct();

        // Primera petición: llena la caché.
        $this->getJson('/api/products/'.$product->id)->assertOk();

        // Segunda petición: si la caché funciona, NO debe haber queries a products.
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson('/api/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('id', $product->id);

        $productQueries = collect(DB::getQueryLog())
            ->filter(fn ($query) => str_contains($query['query'], 'from "products"'));

        $this->assertCount(0, $productQueries);
    }

    public function test_product_cache_is_invalidated_after_purchase(): void
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $headers = $this->authHeaders($user);

        // Llenamos la caché del producto.
        $this->getJson('/api/products/'.$product->id)->assertOk();

        // Compramos una unidad: el stock baja y la caché debe invalidarse.
        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ], $headers)->assertCreated();
        $this->postJson('/api/orders', [], $headers)->assertCreated();

        // Tras la invalidación, consultar el producto vuelve a tocar la BD.
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson('/api/products/'.$product->id)->assertOk();

        $productQueries = collect(DB::getQueryLog())
            ->filter(fn ($query) => str_contains($query['query'], 'from "products"'));

        $this->assertNotSame(0, $productQueries->count());
    }

    public function test_user_cannot_update_another_users_cart_item(): void
    {
        $product = $this->createProduct();
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $itemId = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ], $this->authHeaders($owner))->assertCreated()->json('id');

        $this->patchJson('/api/cart/items/'.$itemId, ['quantity' => 2], $this->authHeaders($other))
            ->assertNotFound()
            ->assertJsonPath('message', 'Cart item not found.');
    }

    // === Tests de búsqueda, filtros y ordenamiento ===

    public function test_products_can_be_searched_by_name(): void
    {
        $this->createProduct(['name' => 'Gaming Laptop Pro']);
        $this->createProduct(['name' => 'Wireless Mouse']);
        $this->createProduct(['name' => 'Mechanical Keyboard']);

        $this->getJson('/api/products?search=laptop')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Gaming Laptop Pro');
    }

    public function test_products_can_be_searched_by_description(): void
    {
        $this->createProduct(['name' => 'Item A', 'description' => 'High-performance graphics card']);
        $this->createProduct(['name' => 'Item B', 'description' => 'Cotton t-shirt']);

        $this->getJson('/api/products?search=graphics')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Item A');
    }

    public function test_search_is_case_insensitive(): void
    {
        $this->createProduct(['name' => 'Laptop']);

        $this->getJson('/api/products?search=LAPTOP')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_products_can_be_filtered_by_min_price(): void
    {
        $this->createProduct(['name' => 'Cheap', 'price' => 10]);
        $this->createProduct(['name' => 'Expensive', 'price' => 500]);

        $this->getJson('/api/products?min_price=100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Expensive');
    }

    public function test_products_can_be_filtered_by_max_price(): void
    {
        $this->createProduct(['name' => 'Cheap', 'price' => 10]);
        $this->createProduct(['name' => 'Expensive', 'price' => 500]);

        $this->getJson('/api/products?max_price=100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Cheap');
    }

    public function test_products_can_be_filtered_by_price_range(): void
    {
        $this->createProduct(['name' => 'Too Cheap', 'price' => 5]);
        $this->createProduct(['name' => 'Just Right', 'price' => 50]);
        $this->createProduct(['name' => 'Too Expensive', 'price' => 500]);

        $this->getJson('/api/products?min_price=20&max_price=100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Just Right');
    }

    public function test_products_can_be_sorted_by_price_ascending(): void
    {
        $this->createProduct(['name' => 'Expensive', 'price' => 500]);
        $this->createProduct(['name' => 'Cheap', 'price' => 10]);

        $this->getJson('/api/products?sort_by=price&sort_dir=asc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Cheap')
            ->assertJsonPath('data.1.name', 'Expensive');
    }

    public function test_products_can_be_sorted_by_price_descending(): void
    {
        $this->createProduct(['name' => 'Expensive', 'price' => 500]);
        $this->createProduct(['name' => 'Cheap', 'price' => 10]);

        $this->getJson('/api/products?sort_by=price&sort_dir=desc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Expensive')
            ->assertJsonPath('data.1.name', 'Cheap');
    }

    public function test_products_can_be_sorted_by_name(): void
    {
        $this->createProduct(['name' => 'Zebra']);
        $this->createProduct(['name' => 'Apple']);

        $this->getJson('/api/products?sort_by=name&sort_dir=asc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Apple')
            ->assertJsonPath('data.1.name', 'Zebra');
    }

    public function test_invalid_sort_column_is_ignored(): void
    {
        $this->createProduct(['name' => 'A']);
        $this->createProduct(['name' => 'B']);

        // sort_by=secret_column should be ignored, default order (latest) applies
        $this->getJson('/api/products?sort_by=secret_column')
            ->assertOk();
    }

    public function test_search_and_filter_can_be_combined(): void
    {
        $this->createProduct(['name' => 'Laptop', 'price' => 100]);
        $this->createProduct(['name' => 'Laptop Pro', 'price' => 500]);
        $this->createProduct(['name' => 'Mouse', 'price' => 10]);

        $this->getJson('/api/products?search=laptop&max_price=200')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laptop');
    }

    public function test_empty_search_returns_all_products(): void
    {
        $this->createProduct(['name' => 'Product A']);
        $this->createProduct(['name' => 'Product B']);

        $this->get('/api/products')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
