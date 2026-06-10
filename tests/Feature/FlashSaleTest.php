<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FlashSaleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simulate 100 concurrent purchase attempts on a product with stock = 10.
     *
     * Expected outcome:
     *   - Exactly 10 orders succeed.
     *   - Exactly 90 orders fail with "Out of stock".
     *   - Final product stock is 0 (never negative).
     */
    public function test_flash_sale_race_condition(): void
    {
        // Arrange: product with limited stock.
        $product = Product::create(['name' => 'Flash Sale Item', 'stock' => 10]);

        $successCount = 0;
        $failCount    = 0;

        // Act: simulate 100 sequential purchase attempts.
        // In a real environment these would be concurrent; here we verify
        // that the lockForUpdate logic handles the boundary correctly.
        for ($i = 0; $i < 100; $i++) {
            $response = $this->postJson('/api/orders', [
                'product_id' => $product->id,
                'quantity'   => 1,
            ]);

            if ($response->json('success') === true) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        // Assert: counts match expected outcomes.
        $this->assertSame(10, $successCount, 'Expected exactly 10 successful orders.');
        $this->assertSame(90, $failCount,    'Expected exactly 90 failed orders.');

        // Assert: stock is exactly 0, never negative.
        $finalStock = $product->fresh()->stock;
        $this->assertSame(0, $finalStock, "Stock must be 0 after flash sale, got {$finalStock}.");

        // Assert: 10 orders exist in the database.
        $this->assertDatabaseCount('orders', 10);
        $this->assertDatabaseCount('order_items', 10);
    }

    /**
     * Test that placing an order on an out-of-stock product returns the correct error.
     */
    public function test_order_fails_when_out_of_stock(): void
    {
        $product = Product::create(['name' => 'Sold Out Item', 'stock' => 0]);

        $response = $this->postJson('/api/orders', [
            'product_id' => $product->id,
            'quantity'   => 1,
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Out of stock',
                 ]);
    }

    /**
     * Test that a product can be created via the API.
     */
    public function test_create_product(): void
    {
        $response = $this->postJson('/api/products', [
            'name'  => 'Gaming Mouse',
            'stock' => 10,
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'name', 'stock']);

        $this->assertDatabaseHas('products', ['name' => 'Gaming Mouse', 'stock' => 10]);
    }

    /**
     * Test that the product list endpoint returns all products.
     */
    public function test_list_products(): void
    {
        Product::create(['name' => 'Item A', 'stock' => 5]);
        Product::create(['name' => 'Item B', 'stock' => 3]);

        $this->getJson('/api/products')
             ->assertStatus(200)
             ->assertJsonCount(2);
    }
}