<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BtcBlocksApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::store((string) config('services.blockstream.cache_store'))->flush();
    }

    public function test_it_returns_latest_blocks_from_blockstream_with_default_limit_of_ten(): void
    {
        Http::fake([
            'https://blockstream.info/api/blocks' => Http::response($this->fakeBlocks(12), 200),
            'https://blockstream.info/api/block/*/txids' => Http::response(['txid-a', 'txid-b'], 200),
        ]);

        $response = $this->getJson('/api/v1/btc/blocks');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'data.blocks')
            ->assertJsonPath('data.blocks.0.hash', 'block-hash-1')
            ->assertJsonPath('data.blocks.0.total_transactions', 3001)
            ->assertJsonPath('data.blocks.0.transactions.0', 'txid-a')
            ->assertJsonStructure([
                'data' => [
                    'blocks' => [
                        [
                            'hash',
                            'weight',
                            'height',
                            'total_transactions',
                            'transactions',
                            'timestamp',
                            'size',
                            'difficulty',
                            'nonce',
                            'merkle_root',
                        ],
                    ],
                ],
            ]);
    }

    public function test_it_validates_limit_query_parameter(): void
    {
        $response = $this->getJson('/api/v1/btc/blocks?limit=101');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['limit']);
    }

    public function test_it_returns_empty_blocks_if_blockstream_is_unavailable(): void
    {
        Http::fake([
            'https://blockstream.info/api/blocks' => Http::response([], 500),
        ]);

        $response = $this->getJson('/api/v1/btc/blocks');

        $response->assertExactJson([
            'data' => [
                'blocks' => [],
            ],
        ]);
    }

    public function test_it_limits_each_block_transactions_to_twenty_five(): void
    {
        Http::fake([
            'https://blockstream.info/api/blocks' => Http::response($this->fakeBlocks(1), 200),
            'https://blockstream.info/api/block/*/txids' => Http::response($this->fakeTxids(40), 200),
        ]);

        $response = $this->getJson('/api/v1/btc/blocks?limit=1');

        $response
            ->assertOk()
            ->assertJsonCount(25, 'data.blocks.0.transactions')
            ->assertJsonPath('data.blocks.0.transactions.0', 'txid-1')
            ->assertJsonPath('data.blocks.0.transactions.24', 'txid-25');
    }

    public function test_it_caches_blockstream_response_for_repeated_requests(): void
    {
        Http::fake([
            'https://blockstream.info/api/blocks' => Http::response($this->fakeBlocks(1), 200),
            'https://blockstream.info/api/block/*/txids' => Http::response($this->fakeTxids(3), 200),
        ]);

        $this->getJson('/api/v1/btc/blocks?limit=1')->assertOk();
        $this->getJson('/api/v1/btc/blocks?limit=1')->assertOk();

        // First request makes two upstream calls (/blocks + /block/:hash/txids).
        // Second request is served from cache.
        Http::assertSentCount(2);
    }

    /**
     * @return list<array<string, int|string>>
     */
    private function fakeBlocks(int $count): array
    {
        $blocks = [];

        for ($i = 1; $i <= $count; $i++) {
            $blocks[] = [
                'id' => "block-hash-{$i}",
                'weight' => 3_990_000 + $i,
                'height' => 900_000 - $i,
                'tx_count' => 3_000 + $i,
                'timestamp' => 1_700_000_000 + $i,
                'size' => 1_500_000 + $i,
                'difficulty' => '89762456972366.31255187',
                'nonce' => 1_234_567 + $i,
                'merkle_root' => "merkle-root-{$i}",
            ];
        }

        return $blocks;
    }

    /**
     * @return list<string>
     */
    private function fakeTxids(int $count): array
    {
        $txids = [];

        for ($i = 1; $i <= $count; $i++) {
            $txids[] = "txid-{$i}";
        }

        return $txids;
    }
}
