<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class RaceConditionTest extends TestCase
{
    use DatabaseMigrations;

    private Process $process;

    protected function setUp(): void
    {
        parent::setUp();

        // Start local PHP server on port 8085 pointing to the public directory.
        // We pass APP_ENV = testing so the server reads .env.testing (configured with online_store_test_db).
        $this->process = new Process(
            ['php', '-S', '127.0.0.1:8085', '-t', 'public'],
            base_path(),
            ['APP_ENV' => 'testing']
        );
        
        $this->process->start();

        // Give the local server a moment to start up
        usleep(500000); // 500ms
    }

    protected function tearDown(): void
    {
        // Stop the local PHP server process
        $this->process->stop();
        parent::tearDown();
    }

    /**
     * Test the API's ability to handle race conditions during a flash sale.
     * We create a product with stock = 5, and fire 20 concurrent orders.
     * Exactly 5 orders must succeed, 15 must fail, and stock must end at 0.
     */
    public function test_flash_sale_race_condition(): void
    {
        // 1. Create a product with limited stock in the test database
        $stockLimit = 5;
        $product = Product::create([
            'name' => 'Limited Flash Sale iPhone',
            'price' => 199.99,
            'stock' => $stockLimit,
        ]);

        $totalRequests = 20;
        $mh = curl_multi_init();
        $handles = [];

        // 2. Initialize 20 concurrent HTTP requests to our local test server
        for ($i = 0; $i < $totalRequests; $i++) {
            $ch = curl_init('http://127.0.0.1:8085/api/orders');
            
            $payload = json_encode([
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ]
                ]
            ]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
            ]);

            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }

        // 3. Execute all requests concurrently
        $active = null;
        do {
            $status = curl_multi_exec($mh, $active);
        } while ($status === CURLM_CALL_MULTI_PERFORM);

        while ($active && $status === CURLM_OK) {
            if (curl_multi_select($mh) !== -1) {
                do {
                    $status = curl_multi_exec($mh, $active);
                } while ($status === CURLM_CALL_MULTI_PERFORM);
            }
        }

        // 4. Gather and assert responses
        $successCount = 0;
        $failureCount = 0;
        $responseDetails = [];

        foreach ($handles as $ch) {
            $info = curl_getinfo($ch);
            $responseBody = curl_multi_getcontent($ch);
            $httpCode = $info['http_code'];

            if ($httpCode === 201) {
                $successCount++;
            } elseif ($httpCode === 422) {
                $failureCount++;
            } else {
                // Keep track of unexpected responses for debugging
                $responseDetails[] = [
                    'code' => $httpCode,
                    'body' => $responseBody
                ];
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        // Debug output if test fails
        if ($successCount !== $stockLimit) {
            echo "\n--- Concurrent Test Debug Info ---\n";
            echo "Successful Orders: $successCount (Expected: $stockLimit)\n";
            echo "Failed Orders (422): $failureCount\n";
            echo "Unexpected responses count: " . count($responseDetails) . "\n";
            foreach ($responseDetails as $detail) {
                echo "Code: {$detail['code']}, Body: {$detail['body']}\n";
            }
            echo "---------------------------------\n";
        }

        // 5. Assertions
        $this->assertEquals($stockLimit, $successCount, "Exactly {$stockLimit} orders should succeed.");
        $this->assertEquals($totalRequests - $stockLimit, $failureCount, "Exactly " . ($totalRequests - $stockLimit) . " orders should fail due to stock depletion.");

        // Reload the product from database to verify stock is exactly 0 and NOT negative
        $product->refresh();
        $this->assertEquals(0, $product->stock, "Final product stock must be exactly 0.");

        // Verify total orders created in database is exactly 5
        $orderCount = Order::count();
        $this->assertEquals($stockLimit, $orderCount, "Total orders created in database must equal successful count.");

        // Verify total order items matches
        $orderItemsCount = OrderItem::count();
        $this->assertEquals($stockLimit, $orderItemsCount, "Total order items count in database must match.");
    }
}
