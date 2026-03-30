<?php

namespace Tests\Unit;

use App\DataTransferObjects\BtcBlockData;
use PHPUnit\Framework\TestCase;

class BtcBlockDataTest extends TestCase
{
    public function test_it_serializes_to_the_expected_api_shape(): void
    {
        $block = new BtcBlockData(
            hash: '0000000000000000000238b4c7f36fef6f1759e68d5974f274b58276a2fca001',
            weight: 3992032,
            height: 891234,
            totalTransactions: 2689,
            transactions: [
                'fce7cbcb57bcfbe3083bdbf531f6f301f6b66d8402ec4efd9ecf8b17e5f0d4a5',
            ],
            timestamp: 1711339557,
            size: 1579132,
            difficulty: '89762456972366.31255187',
            nonce: 1234567890,
            merkleRoot: 'a43f842dd5931b4b12653bf1e4b3f6f2168f8d5a5fb80b6f63f100e01085cb2a',
        );

        $this->assertSame([
            'hash' => '0000000000000000000238b4c7f36fef6f1759e68d5974f274b58276a2fca001',
            'weight' => 3992032,
            'height' => 891234,
            'total_transactions' => 2689,
            'transactions' => [
                'fce7cbcb57bcfbe3083bdbf531f6f301f6b66d8402ec4efd9ecf8b17e5f0d4a5',
            ],
            'timestamp' => 1711339557,
            'size' => 1579132,
            'difficulty' => '89762456972366.31255187',
            'nonce' => 1234567890,
            'merkle_root' => 'a43f842dd5931b4b12653bf1e4b3f6f2168f8d5a5fb80b6f63f100e01085cb2a',
        ], $block->toArray());
    }
}
