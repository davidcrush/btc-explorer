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

    public function test_it_returns_block_details_with_navigation_hashes(): void
    {
        Http::fake([
            'https://blockstream.info/api/block/block-hash-1' => Http::response(
                $this->fakeBlockDetail('block-hash-1', 900_000, 'prev-hash'),
                200
            ),
            'https://blockstream.info/api/block/block-hash-1/txids' => Http::response($this->fakeTxids(40), 200),
            'https://blockstream.info/api/block-height/900001' => Http::response('next-hash', 200),
        ]);

        $response = $this->getJson('/api/v1/btc/blocks/block-hash-1');

        $response
            ->assertOk()
            ->assertJsonPath('data.block.hash', 'block-hash-1')
            ->assertJsonPath('data.block.previous_block_hash', 'prev-hash')
            ->assertJsonPath('data.block.next_block_hash', 'next-hash')
            ->assertJsonPath('data.block.total_transactions', 3200)
            ->assertJsonCount(25, 'data.block.transactions');
    }

    public function test_it_returns_null_navigation_when_previous_or_next_do_not_exist(): void
    {
        Http::fake([
            'https://blockstream.info/api/block/genesis-hash' => Http::response(
                $this->fakeBlockDetail('genesis-hash', 0, null),
                200
            ),
            'https://blockstream.info/api/block/genesis-hash/txids' => Http::response($this->fakeTxids(2), 200),
            'https://blockstream.info/api/block-height/1' => Http::response('', 404),
        ]);

        $response = $this->getJson('/api/v1/btc/blocks/genesis-hash');

        $response
            ->assertOk()
            ->assertJsonPath('data.block.previous_block_hash', null)
            ->assertJsonPath('data.block.next_block_hash', null);
    }

    public function test_it_returns_not_found_when_block_does_not_exist(): void
    {
        Http::fake([
            'https://blockstream.info/api/block/unknown-hash' => Http::response('', 404),
        ]);

        $response = $this->getJson('/api/v1/btc/blocks/unknown-hash');

        $response->assertNotFound();
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

    /**
     * @return array<string, int|string|null>
     */
    private function fakeBlockDetail(string $hash, int $height, ?string $previousHash): array
    {
        return [
            'id' => $hash,
            'height' => $height,
            'version' => 123456,
            'timestamp' => 1_700_100_000,
            'mediantime' => 1_700_099_800,
            'bits' => '170d5f7a',
            'nonce' => 987654,
            'merkle_root' => 'detail-merkle-root',
            'tx_count' => 3200,
            'size' => 1_700_000,
            'weight' => 3_990_000,
            'difficulty' => '90523123123.111',
            'previousblockhash' => $previousHash,
        ];
    }
}
