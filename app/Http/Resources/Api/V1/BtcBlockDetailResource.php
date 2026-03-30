<?php

namespace App\Http\Resources\Api\V1;

use App\DataTransferObjects\BtcBlockDetailData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BtcBlockDetailData */
class BtcBlockDetailResource extends JsonResource
{
    /**
     * @return array{
     *     hash: string,
     *     height: int,
     *     version: int,
     *     timestamp: int,
     *     mediantime: int,
     *     bits: string,
     *     nonce: int,
     *     merkle_root: string,
     *     total_transactions: int,
     *     size: int,
     *     weight: int,
     *     difficulty: string,
     *     previous_block_hash: ?string,
     *     next_block_hash: ?string,
     *     transactions: list<string>
     * }
     */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
