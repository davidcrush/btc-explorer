<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BtcMempoolApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::store((string) config('services.blockstream.cache_store'))->flush();
    }

    public function test_it_returns_mempool_stats(): void
    {
        Http::fake(function (Request $request) {
            $path = parse_url($request->url(), PHP_URL_PATH) ?? '';

            if (str_ends_with($path, '/mempool/txids')) {
                return Http::response([], 200);
            }

            if (str_ends_with($path, '/mempool')) {
                return Http::response([
                    'count' => 100,
                    'vsize' => 200_000,
                    'total_fee' => 1_500_000,
                    'fee_histogram' => [[10.5, 5000], [5.0, 8000]],
                ], 200);
            }

            return Http::response('', 404);
        });

        $response = $this->getJson('/api/v1/btc/mempool/stats');

        $response
            ->assertOk()
            ->assertJsonPath('data.stats.count', 100)
            ->assertJsonPath('data.stats.vsize', 200_000)
            ->assertJsonPath('data.stats.total_fee', 1_500_000)
            ->assertJsonCount(2, 'data.stats.fee_histogram');
    }

    public function test_it_returns_paginated_mempool_txids_without_tx_detail_calls(): void
    {
        $txids = [];

        for ($i = 1; $i <= 30; $i++) {
            $txids[] = sprintf('%064x', $i);
        }

        Http::fake(function (Request $request) use ($txids) {
            $path = parse_url($request->url(), PHP_URL_PATH) ?? '';

            if (str_ends_with($path, '/mempool/txids')) {
                return Http::response($txids, 200);
            }

            if (str_ends_with($path, '/mempool')) {
                return Http::response([
                    'count' => 30,
                    'vsize' => 1000,
                    'total_fee' => 100,
                    'fee_histogram' => [],
                ], 200);
            }

            return Http::response('', 404);
        });

        $first = $this->getJson('/api/v1/btc/mempool/transactions?offset=0&limit=25');

        $first
            ->assertOk()
            ->assertJsonPath('data.total_count', 30)
            ->assertJsonPath('data.offset', 0)
            ->assertJsonPath('data.limit', 25)
            ->assertJsonPath('data.has_more', true)
            ->assertJsonCount(25, 'data.transactions')
            ->assertJsonPath('data.transactions.0.txid', sprintf('%064x', 1));

        $this->assertArrayNotHasKey('fee', $first->json('data.transactions.0') ?? []);

        $second = $this->getJson('/api/v1/btc/mempool/transactions?offset=25&limit=25');

        $second
            ->assertOk()
            ->assertJsonPath('data.has_more', false)
            ->assertJsonCount(5, 'data.transactions');

        Http::assertNotSent(function (Request $request): bool {
            $path = parse_url($request->url(), PHP_URL_PATH) ?? '';

            return str_contains($path, '/tx/');
        });
    }

    public function test_it_validates_mempool_transactions_limit(): void
    {
        $response = $this->getJson('/api/v1/btc/mempool/transactions?limit=26');

        $response->assertUnprocessable()->assertJsonValidationErrors(['limit']);
    }

    public function test_it_returns_502_when_mempool_stats_upstream_fails(): void
    {
        Http::fake([
            'https://blockstream.info/api/mempool' => Http::response('', 500),
        ]);

        $response = $this->getJson('/api/v1/btc/mempool/stats');

        $response
            ->assertStatus(502)
            ->assertJsonPath('message', 'Failed to load mempool statistics.');
    }
}
