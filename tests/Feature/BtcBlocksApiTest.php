<?php

namespace Tests\Feature;

use Tests\TestCase;

class BtcBlocksApiTest extends TestCase
{
    public function test_it_returns_an_empty_blocks_stub_response(): void
    {
        $response = $this->getJson('/api/v1/btc/blocks');

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'blocks' => [],
                ],
            ]);
    }
}
