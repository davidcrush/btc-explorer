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
            bits: '170d5f7a',
            nonce: 12345,
            merkleRoot: 'detail-merkle-root',
            txCount: 3200,
            size: 1700000,
            weight: 3990000,
            difficulty: '90523123123.111',
            previousBlockHash: 'prev-hash',
            nextBlockHash: 'next-hash',
            transactions: ['txid-1', 'txid-2'],
        );

        $this->assertSame([
            'hash' => 'detail-hash',
            'height' => 900001,
            'version' => 123456,
            'timestamp' => 1700100000,
            'mediantime' => 1700099800,
            'bits' => '170d5f7a',
            'nonce' => 12345,
            'merkle_root' => 'detail-merkle-root',
            'total_transactions' => 3200,
            'size' => 1700000,
            'weight' => 3990000,
            'difficulty' => '90523123123.111',
            'previous_block_hash' => 'prev-hash',
            'next_block_hash' => 'next-hash',
            'transactions' => ['txid-1', 'txid-2'],
        ], $block->toArray());
    }
}
