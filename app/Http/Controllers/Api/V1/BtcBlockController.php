<?php

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\BtcBlockData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListBtcBlocksRequest;
use App\Http\Resources\Api\V1\BtcBlockResource;
use Illuminate\Http\JsonResponse;

class BtcBlockController extends Controller
{
    public function index(ListBtcBlocksRequest $request): JsonResponse
    {
        $limit = (int) $request->validated('limit', 10);

        $blocks = BtcBlockResource::collection($this->fetchLatestBlocks($limit))->resolve();

        return response()->json([
            'data' => [
                'blocks' => $blocks,
            ],
        ]);
    }

    /**
     * @return list<BtcBlockData>
     */
    private function fetchLatestBlocks(int $limit): array
    {
        // Stub integration point for the blockchain data source.
        return [];
    }
}
