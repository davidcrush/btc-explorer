<?php

namespace App\Http\Resources\Api\V1;

use App\DataTransferObjects\BtcMempoolStatsData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BtcMempoolStatsData */
class BtcMempoolStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
