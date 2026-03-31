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
            'https://blockstream.info/api/block/*/txs*' => Http::response($this->fakeTransactions(2), 200),
        ]);

        $response = $this->getJson('/api/v1/btc/blocks');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'data.blocks')
            ->assertJsonPath('data.blocks.0.hash', 'block-hash-1')
            ->assertJsonPath('data.blocks.0.miner', 'FakePool')
            ->assertJsonPath('data.blocks.0.block_reward', 5000000000)
            ->assertJsonPath('data.blocks.0.total_fees', 4687500000)
            ->assertJsonPath('data.blocks.0.total_transactions', 3001)
            ->assertJsonPath('data.blocks.0.transactions.0', 'txid-1')
            ->assertJsonStructure([
                'data' => [
                    'blocks' => [
                        [
                            'hash',
                            'weight',
                            'height',
                            'miner',
                            'block_reward',
                            'total_fees',
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

    public function test_it_pages_upstream_blocks_when_limit_exceeds_ten(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if (str_ends_with(parse_url($url, PHP_URL_PATH) ?? '', '/blocks')) {
                return Http::response($this->fakeBlocksAtHeights(900_010, 10), 200);
            }

            if (str_ends_with($url, '/blocks/900000')) {
                return Http::response($this->fakeBlocksAtHeights(900_000, 10), 200);
            }

            if (str_ends_with($url, '/blocks/899990')) {
                return Http::response($this->fakeBlocksAtHeights(899_990, 5), 200);
            }

            if (str_contains($url, '/txs')) {
                return Http::response($this->fakeTransactions(2), 200);
            }

            return Http::response('', 404);
        });

        $response = $this->getJson('/api/v1/btc/blocks?limit=25');

        $response
            ->assertOk()
            ->assertJsonCount(25, 'data.blocks')
            ->assertJsonPath('data.blocks.0.height', 900_010)
            ->assertJsonPath('data.blocks.9.height', 900_001)
            ->assertJsonPath('data.blocks.10.height', 900_000)
            ->assertJsonPath('data.blocks.24.height', 899_986);
    }

    public function test_it_limits_each_block_transactions_to_twenty_five(): void
    {
        Http::fake([
            'https://blockstream.info/api/blocks' => Http::response($this->fakeBlocks(1), 200),
            'https://blockstream.info/api/block/*/txs*' => Http::response($this->fakeTransactions(40), 200),
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
            'https://blockstream.info/api/block/*/txs*' => Http::response($this->fakeTransactions(3), 200),
        ]);

        $this->getJson('/api/v1/btc/blocks?limit=1')->assertOk();
        $this->getJson('/api/v1/btc/blocks?limit=1')->assertOk();

        // First request makes three upstream calls (/blocks + /block/:hash/txs for tx list + economics).
        // Second request is served from cache.
        Http::assertSentCount(3);
    }

    public function test_it_returns_block_details_with_navigation_hashes(): void
    {
        Http::fake([
            'https://blockstream.info/api/block/block-hash-1' => Http::response(
                $this->fakeBlockDetail('block-hash-1', 900_000, 'prev-hash'),
                200
            ),
            'https://blockstream.info/api/block/block-hash-1/txs' => Http::response($this->fakeTransactions(25), 200),
            'https://blockstream.info/api/block-height/900001' => Http::response('next-hash', 200),
        ]);

        $response = $this->getJson('/api/v1/btc/blocks/block-hash-1');

        $response
            ->assertOk()
            ->assertJsonPath('data.block.hash', 'block-hash-1')
            ->assertJsonPath('data.block.previous_block_hash', 'prev-hash')
            ->assertJsonPath('data.block.next_block_hash', 'next-hash')
            ->assertJsonPath('data.block.miner', 'FakePool')
            ->assertJsonPath('data.block.block_reward', 5000000000)
            ->assertJsonPath('data.block.total_fees', 4687500000)
            ->assertJsonPath('data.block.total_transactions', 3200)
            ->assertJsonPath('data.block.transactions.0.is_coinbase', true)
            ->assertJsonPath('data.block.transactions.0.output_total', 5000000000)
            ->assertJsonPath('data.block.has_more_transactions', true)
            ->assertJsonPath('data.block.next_transactions_start', 25)
            ->assertJsonCount(25, 'data.block.transactions');
    }

    public function test_it_supports_loading_next_transaction_page(): void
    {
        Http::fake([
            'https://blockstream.info/api/block/block-hash-1' => Http::response(
                $this->fakeBlockDetail('block-hash-1', 900_000, 'prev-hash'),
                200
            ),
            'https://blockstream.info/api/block/block-hash-1/txs' => Http::response($this->fakeTransactions(25), 200),
            'https://blockstream.info/api/block/block-hash-1/txs/25' => Http::response($this->fakeTransactions(25, 26), 200),
            'https://blockstream.info/api/block-height/900001' => Http::response('next-hash', 200),
        ]);

        $response = $this->getJson('/api/v1/btc/blocks/block-hash-1?transactions_start=25');

        $response
            ->assertOk()
            ->assertJsonPath('data.block.transactions_start', 25)
            ->assertJsonPath('data.block.transactions.0.txid', 'txid-26')
            ->assertJsonPath('data.block.has_more_transactions', true)
            ->assertJsonPath('data.block.next_transactions_start', 50);
    }

    public function test_it_returns_null_navigation_when_previous_or_next_do_not_exist(): void
    {
        Http::fake([
            'https://blockstream.info/api/block/genesis-hash' => Http::response(
                $this->fakeBlockDetail('genesis-hash', 0, null),
                200
            ),
            'https://blockstream.info/api/block/genesis-hash/txs' => Http::response($this->fakeTransactions(2), 200),
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
     * @return list<array<string, int|string>>
     */
    private function fakeBlocksAtHeights(int $highestHeight, int $count): array
    {
        $blocks = [];

        for ($i = 0; $i < $count; $i++) {
            $height = $highestHeight - $i;
            $blocks[] = [
                'id' => "block-hash-{$height}",
                'weight' => 3_990_000 + $height,
                'height' => $height,
                'tx_count' => 3_000 + ($height % 1000),
                'timestamp' => 1_700_000_000 + $height,
                'size' => 1_500_000 + $height,
                'difficulty' => '89762456972366.31255187',
                'nonce' => 1_234_567 + $height,
                'merkle_root' => "merkle-root-{$height}",
            ];
        }

        return $blocks;
    }

    /**
     * @return list<array{
     *     txid: string,
     *     fee: int,
     *     vin: list<array<string, mixed>>,
     *     vout: list<array<string, mixed>>
     * }>
     */
    private function fakeTransactions(int $count, int $start = 1): array
    {
        $transactions = [];

        for ($i = $start; $i < ($start + $count); $i++) {
            $isCoinbase = $i === 1;
            $transactions[] = [
                'txid' => "txid-{$i}",
                'fee' => $isCoinbase ? 0 : 2100,
                'vin' => $isCoinbase
                    ? [
                        [
                            'is_coinbase' => true,
                            'scriptsig' => '2f46616b65506f6f6c2f',
                        ],
                    ]
                    : [
                        [
                            'is_coinbase' => false,
                            'txid' => "input-tx-{$i}",
                            'vout' => 0,
                            'prevout' => [
                                'value' => 1500,
                                'scriptpubkey_address' => "in-address-{$i}",
                            ],
                        ],
                    ],
                'vout' => $isCoinbase
                    ? [
                        [
                            'value' => 5000000000,
                            'scriptpubkey_address' => "coinbase-address-{$i}",
                        ],
                    ]
                    : [
                        [
                            'value' => 900,
                            'scriptpubkey_address' => "out-a-{$i}",
                        ],
                        [
                            'value' => 500,
                            'scriptpubkey_address' => "out-b-{$i}",
                        ],
                    ],
            ];
        }

        return $transactions;
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
