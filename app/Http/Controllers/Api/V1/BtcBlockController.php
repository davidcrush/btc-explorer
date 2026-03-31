<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListBtcBlocksRequest;
use App\Http\Resources\Api\V1\BtcBlockDetailResource;
use App\Http\Resources\Api\V1\BtcBlockResource;
use App\Services\BlockstreamApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BtcBlockController extends Controller
{
    public function __construct(
        private readonly BlockstreamApiClient $blockstreamApiClient,
    ) {}

    public function index(ListBtcBlocksRequest $request): JsonResponse
    {
        $limit = (int) $request->validated('limit', 10);
        $offset = (int) $request->validated('offset', 0);

        try {
            $blocks = BtcBlockResource::collection($this->blockstreamApiClient->latestBlocks($limit, $offset))->resolve();
        } catch (RuntimeException $e) {
            Log::warning('btc_blocks_index_failed', [
                'message' => $e->getMessage(),
                'limit' => $limit,
                'offset' => $offset,
            ]);

            return response()->json([
                'message' => $e->getMessage(),
                'data' => [
                    'blocks' => [],
                    'has_more' => false,
                ],
            ], 502);
        }

        return response()->json([
            'data' => [
                'blocks' => $blocks,
                'has_more' => count($blocks) === $limit,
            ],
        ]);
    }

    public function show(Request $request, string $hash): JsonResponse
    {
        $validated = $request->validate([
            'transactions_start' => ['sometimes', 'integer', 'min:0'],
            'transactions_limit' => ['sometimes', 'integer', 'min:1', 'max:25'],
        ]);

        $transactionsStart = (int) ($validated['transactions_start'] ?? 0);
        $transactionsLimit = (int) ($validated['transactions_limit'] ?? 25);

        try {
            $block = $this->blockstreamApiClient->blockDetails($hash, $transactionsStart, $transactionsLimit);
        } catch (RuntimeException) {
            return response()->json([
                'message' => 'Failed to load block details.',
            ], 502);
        }

        if ($block === null) {
            return response()->json([
                'message' => 'Block not found.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'block' => (new BtcBlockDetailResource($block))->resolve(),
            ],
        ]);
    }
}
