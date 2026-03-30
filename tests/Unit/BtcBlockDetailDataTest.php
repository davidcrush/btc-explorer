<?php

namespace Tests\Unit;

use App\DataTransferObjects\BtcBlockDetailData;
use PHPUnit\Framework\TestCase;

class BtcBlockDetailDataTest extends TestCase
{
    public function test_it_serializes_block_detail_to_expected_shape(): void
    {
        $block = new BtcBlockDetailData(
            hash: 'detail-hash',
            height: 900001,
            version: 123456,
            timestamp: 1700100000,
            mediantime: 1700099800,
            miner: 'FakePool',
            bits: '170d5f7a',
            nonce: 12345,
            merkleRoot: 'detail-merkle-root',
            blockReward: 312500000,
            totalFees: 12000,
            txCount: 3200,
            size: 1700000,
            weight: 3990000,
            difficulty: '90523123123.111',
            previousBlockHash: 'prev-hash',
            nextBlockHash: 'next-hash',
            transactionsStart: 0,
            transactionsLimit: 25,
            hasMoreTransactions: true,
            nextTransactionsStart: 25,
            transactions: [
                [
                    'txid' => 'txid-1',
                    'is_coinbase' => true,
                    'fee' => 0,
                    'input_total' => 0,
                    'output_total' => 5000000000,
                    'inputs' => [
                        [
                            'txid' => null,
                            'vout' => null,
                            'address' => null,
                            'value' => 0,
                            'is_coinbase' => true,
                        ],
                    ],
                    'outputs' => [
                        [
                            'address' => 'coinbase-address',
                            'value' => 5000000000,
                        ],
                    ],
                ],
            ],
        );

        $this->assertSame([
            'hash' => 'detail-hash',
            'height' => 900001,
            'version' => 123456,
            'timestamp' => 1700100000,
            'mediantime' => 1700099800,
            'miner' => 'FakePool',
            'bits' => '170d5f7a',
            'nonce' => 12345,
            'merkle_root' => 'detail-merkle-root',
            'block_reward' => 312500000,
            'total_fees' => 12000,
            'total_transactions' => 3200,
            'size' => 1700000,
            'weight' => 3990000,
            'difficulty' => '90523123123.111',
            'previous_block_hash' => 'prev-hash',
            'next_block_hash' => 'next-hash',
            'transactions_start' => 0,
            'transactions_limit' => 25,
            'has_more_transactions' => true,
            'next_transactions_start' => 25,
            'transactions' => [
                [
                    'txid' => 'txid-1',
                    'is_coinbase' => true,
                    'fee' => 0,
                    'input_total' => 0,
                    'output_total' => 5000000000,
                    'inputs' => [
                        [
                            'txid' => null,
                            'vout' => null,
                            'address' => null,
                            'value' => 0,
                            'is_coinbase' => true,
                        ],
                    ],
                    'outputs' => [
                        [
                            'address' => 'coinbase-address',
                            'value' => 5000000000,
                        ],
                    ],
                ],
            ],
        ], $block->toArray());
    }
}
