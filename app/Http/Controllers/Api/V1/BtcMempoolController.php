<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListMempoolTransactionsRequest;
use App\Http\Resources\Api\V1\BtcMempoolStatsResource;
use App\Services\BlockstreamApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BtcMempoolController extends Controller
{
    public function __construct(
        private readonly BlockstreamApiClient $blockstreamApiClient,
    ) {}

    public function stats(): JsonResponse
    {
        try {
            $stats = $this->blockstreamApiClient->mempoolStats();
        } catch (RuntimeException $e) {
            Log::warning('btc_mempool_stats_failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to load mempool statistics.',
            ], 502);
        }

        return response()->json([
            'data' => [
                'stats' => (new BtcMempoolStatsResource($stats))->resolve(),
            ],
        ]);
    }

    public function transactions(ListMempoolTransactionsRequest $request): JsonResponse
    {
        $offset = (int) $request->validated('offset', 0);
        $limit = (int) $request->validated('limit', 25);

        try {
            $payload = $this->blockstreamApiClient->mempoolTransactions($offset, $limit);
        } catch (RuntimeException $e) {
            Log::warning('btc_mempool_transactions_failed', [
                'message' => $e->getMessage(),
                'offset' => $offset,
                'limit' => $limit,
            ]);

            return response()->json([
                'message' => 'Failed to load mempool transactions.',
            ], 502);
        }

        $totalCount = $payload['total_count'];
        $normalizedLimit = $payload['limit'];

        return response()->json([
            'data' => [
                'transactions' => $payload['transactions'],
                'total_count' => $totalCount,
                'offset' => $offset,
                'limit' => $normalizedLimit,
                'has_more' => ($offset + $normalizedLimit) < $totalCount,
            ],
        ]);
    }
}
