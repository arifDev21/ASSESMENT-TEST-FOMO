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

        $this->process = new Process(
            ['php', '-S', '127.0.0.1:8085', '-t', 'public'],
            base_path(),
            ['APP_ENV' => 'testing']
        );
        $this->process->start();

        usleep(500000);
    }

    protected function tearDown(): void
    {
        $this->process->stop();
        parent::tearDown();
    }

    public function test_flash_sale_race_condition(): void
    {
        $stockLimit = 5;
        $product = Product::create([
            'name' => 'Limited Flash Sale iPhone',
            'price' => 199.99,
            'stock' => $stockLimit,
        ]);

        $totalRequests = 20;
        $mh = curl_multi_init();
        $handles = [];

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

        $successCount = 0;
        $failureCount = 0;

        foreach ($handles as $ch) {
            $info = curl_getinfo($ch);
            $httpCode = $info['http_code'];

            if ($httpCode === 201) {
                $successCount++;
            } elseif ($httpCode === 422) {
                $failureCount++;
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        $this->assertEquals($stockLimit, $successCount);
        $this->assertEquals($totalRequests - $stockLimit, $failureCount);

        $product->refresh();
        $this->assertEquals(0, $product->stock);

        $this->assertEquals($stockLimit, Order::count());
        $this->assertEquals($stockLimit, OrderItem::count());
    }
}
