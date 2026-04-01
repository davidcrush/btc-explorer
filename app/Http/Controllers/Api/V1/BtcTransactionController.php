<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BtcTransactionDetailResource;
use App\Http\Resources\Api\V1\BtcTransactionSummaryResource;
use App\Services\BlockstreamApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BtcTransactionController extends Controller
{
    public function __construct(
        private readonly BlockstreamApiClient $blockstreamApiClient,
    ) {}

    public function recent(): JsonResponse
    {
        try {
            $transactions = $this->blockstreamApiClient->recentConfirmedTransactions(10);
        } catch (RuntimeException $e) {
            Log::warning('btc_transactions_recent_failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to load recent transactions.',
            ], 502);
        }

        return response()->json([
            'data' => [
                'transactions' => collect($transactions)
                    ->map(static fn ($tx) => (new BtcTransactionSummaryResource($tx))->resolve())
                    ->all(),
            ],
        ]);
    }

    public function show(string $txid): JsonResponse
    {
        try {
            $transaction = $this->blockstreamApiClient->transactionDetails($txid);
        } catch (RuntimeException $e) {
            Log::warning('btc_transaction_show_failed', [
                'message' => $e->getMessage(),
                'txid' => $txid,
            ]);

            return response()->json([
                'message' => 'Failed to load transaction details.',
            ], 502);
        }

        if ($transaction === null) {
            return response()->json([
                'message' => 'Transaction not found.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'transaction' => (new BtcTransactionDetailResource($transaction))->resolve(),
            ],
        ]);
    }
}
