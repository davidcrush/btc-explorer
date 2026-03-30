<?php

namespace App\Http\Resources\Api\V1;

use App\DataTransferObjects\BtcBlockData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BtcBlockData */
class BtcBlockResource extends JsonResource
{
    /**
     * @return array{
     *     hash: string,
     *     weight: int,
     *     height: int,
     *     transactions: list<string>,
     *     timestamp: int,
     *     size: int,
     *     difficulty: string,
     *     nonce: int,
     *     merkle_root: string
     * }
     */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
