<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListBtcBlocksRequest;
use App\Http\Resources\Api\V1\BtcBlockResource;
use App\Services\BlockstreamApiClient;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class BtcBlockController extends Controller
{
    public function __construct(
        private readonly BlockstreamApiClient $blockstreamApiClient,
    ) {}

    public function index(ListBtcBlocksRequest $request): JsonResponse
    {
        $limit = (int) $request->validated('limit', 10);

        try {
            $blocks = BtcBlockResource::collection($this->blockstreamApiClient->latestBlocks($limit))->resolve();
        } catch (RuntimeException) {
            $blocks = [];
        }

        return response()->json([
            'data' => [
                'blocks' => $blocks,
            ],
        ]);
    }
}
