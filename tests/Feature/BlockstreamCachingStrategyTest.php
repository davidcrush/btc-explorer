<?php

namespace Tests\Feature;

use App\Services\BlockstreamApiClient;
use ReflectionMethod;
use Tests\TestCase;

class BlockstreamCachingStrategyTest extends TestCase
{
    public function test_it_uses_stable_ttl_for_blocks_with_next_block_hash(): void
    {
        config()->set('services.blockstream.block_detail_hot_ttl', 30);
        config()->set('services.blockstream.block_detail_stable_ttl', 3600);

        $client = app(BlockstreamApiClient::class);

        $ttlResolver = new ReflectionMethod(BlockstreamApiClient::class, 'blockDetailsTtl');
        $ttlResolver->setAccessible(true);

        $ttl = $ttlResolver->invoke($client, [
            'next_block_hash' => 'some-next-hash',
        ]);

        $this->assertSame(3600, $ttl);
    }

    public function test_it_uses_hot_ttl_for_latest_block_without_next_block_hash(): void
    {
        config()->set('services.blockstream.block_detail_hot_ttl', 30);
        config()->set('services.blockstream.block_detail_stable_ttl', 3600);

        $client = app(BlockstreamApiClient::class);

        $ttlResolver = new ReflectionMethod(BlockstreamApiClient::class, 'blockDetailsTtl');
        $ttlResolver->setAccessible(true);

        $ttl = $ttlResolver->invoke($client, [
            'next_block_hash' => null,
        ]);

        $this->assertSame(30, $ttl);
    }
}
