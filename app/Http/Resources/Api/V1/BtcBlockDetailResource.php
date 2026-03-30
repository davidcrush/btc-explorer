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
     *     miner: ?string,
     *     bits: string,
     *     nonce: int,
     *     merkle_root: string,
     *     block_reward: int,
     *     total_fees: int,
     *     total_transactions: int,
     *     size: int,
     *     weight: int,
     *     difficulty: string,
     *     previous_block_hash: ?string,
     *     next_block_hash: ?string,
     *     transactions_start: int,
     *     transactions_limit: int,
     *     has_more_transactions: bool,
     *     next_transactions_start: ?int,
     *     transactions: list<array{
     *         txid: string,
     *         is_coinbase: bool,
     *         fee: int,
     *         input_total: int,
     *         output_total: int,
     *         inputs: list<array{txid: ?string, vout: ?int, address: ?string, value: int, is_coinbase: bool}>,
     *         outputs: list<array{address: ?string, value: int}>
     *     }>
     * }
     */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
