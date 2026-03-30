<?php

namespace Tests\Feature;

use Tests\TestCase;

class BtcBlocksApiTest extends TestCase
{
    public function test_it_returns_an_empty_blocks_response_shape(): void
    {
        $response = $this->getJson('/api/v1/btc/blocks');

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'blocks' => [],
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
}
