<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BtcTransactionApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::store((string) config('services.blockstream.cache_store'))->flush();
    }

    public function test_it_returns_transaction_details_when_blockstream_succeeds(): void
    {
        $txid = str_repeat('a', 64);

        Http::fake([
            "https://blockstream.info/api/tx/{$txid}" => Http::response($this->fakeTxDetail($txid), 200),
        ]);

        $response = $this->getJson("/api/v1/btc/transactions/{$txid}");

        $response
            ->assertOk()
            ->assertJsonPath('data.transaction.txid', $txid)
            ->assertJsonPath('data.transaction.confirmed', true)
            ->assertJsonPath('data.transaction.block_hash', 'block-abc')
            ->assertJsonPath('data.transaction.block_height', 123)
            ->assertJsonPath('data.transaction.fee', 2100)
            ->assertJsonPath('data.transaction.size', 250)
            ->assertJsonPath('data.transaction.weight', 1000)
            ->assertJsonPath('data.transaction.virtual_size', 250)
            ->assertJsonPath('data.transaction.is_coinbase', false)
            ->assertJsonPath('data.transaction.input_total', 5000)
            ->assertJsonPath('data.transaction.output_total', 2900)
            ->assertJsonCount(1, 'data.transaction.inputs')
            ->assertJsonCount(2, 'data.transaction.outputs');
    }

    public function test_it_returns_404_when_transaction_does_not_exist(): void
    {
        $txid = str_repeat('b', 64);

        Http::fake([
            "https://blockstream.info/api/tx/{$txid}" => Http::response('', 404),
        ]);

        $response = $this->getJson("/api/v1/btc/transactions/{$txid}");

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Transaction not found.');
    }

    public function test_it_returns_404_for_invalid_txid_format(): void
    {
        $response = $this->getJson('/api/v1/btc/transactions/not-a-txid');

        $response->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_it_returns_502_when_blockstream_fails(): void
    {
        $txid = str_repeat('c', 64);

        Http::fake([
            "https://blockstream.info/api/tx/{$txid}" => Http::response('', 500),
        ]);

        $response = $this->getJson("/api/v1/btc/transactions/{$txid}");

        $response
            ->assertStatus(502)
            ->assertJsonPath('message', 'Failed to load transaction details.');
    }

    public function test_it_returns_up_to_ten_recent_confirmed_transaction_summaries(): void
    {
        Http::fake([
            'https://blockstream.info/api/blocks' => Http::response([
                [
                    'id' => 'tip-block-hash',
                    'height' => 900_000,
                ],
            ], 200),
            'https://blockstream.info/api/block/tip-block-hash/txs' => Http::response(
                $this->fakeBlockTxSummaries(12),
                200
            ),
        ]);

        $response = $this->getJson('/api/v1/btc/transactions/recent');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'data.transactions')
            ->assertJsonPath('data.transactions.0.txid', sprintf('%064x', 1))
            ->assertJsonPath('data.transactions.0.fee', 100)
            ->assertJsonPath('data.transactions.9.txid', sprintf('%064x', 10));
    }

    public function test_it_returns_502_when_recent_blocks_request_fails(): void
    {
        Http::fake([
            'https://blockstream.info/api/blocks' => Http::response('', 500),
        ]);

        $response = $this->getJson('/api/v1/btc/transactions/recent');

        $response
            ->assertStatus(502)
            ->assertJsonPath('message', 'Failed to load recent transactions.');
    }

    /**
     * @return list<array{txid: string, fee: int}>
     */
    private function fakeBlockTxSummaries(int $count): array
    {
        $list = [];

        for ($i = 1; $i <= $count; $i++) {
            $list[] = [
                'txid' => sprintf('%064x', $i),
                'fee' => $i * 100,
            ];
        }

        return $list;
    }

    /**
     * @return array<string, mixed>
     */
    private function fakeTxDetail(string $txid): array
    {
        return [
            'txid' => $txid,
            'fee' => 2100,
            'size' => 250,
            'weight' => 1000,
            'status' => [
                'confirmed' => true,
                'block_height' => 123,
                'block_hash' => 'block-abc',
            ],
            'vin' => [
                [
                    'is_coinbase' => false,
                    'txid' => 'aa'.str_repeat('0', 62),
                    'vout' => 0,
                    'prevout' => [
                        'value' => 5000,
                        'scriptpubkey_address' => 'bc1qtestinput',
                    ],
                ],
            ],
            'vout' => [
                [
                    'value' => 2500,
                    'scriptpubkey_address' => 'bc1qout1',
                ],
                [
                    'value' => 400,
                    'scriptpubkey_address' => 'bc1qout2',
                ],
            ],
        ];
    }
}
