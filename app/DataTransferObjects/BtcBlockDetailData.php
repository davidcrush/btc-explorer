<?php

namespace App\DataTransferObjects;

final readonly class BtcBlockDetailData
{
    /**
     * @param  list<string>  $transactions
     */
    public function __construct(
        public string $hash,
        public int $height,
        public int $version,
        public int $timestamp,
        public int $mediantime,
        public string $bits,
        public int $nonce,
        public string $merkleRoot,
        public int $txCount,
        public int $size,
        public int $weight,
        public string $difficulty,
        public ?string $previousBlockHash,
        public ?string $nextBlockHash,
        public array $transactions,
    ) {}

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
    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
            'height' => $this->height,
            'version' => $this->version,
            'timestamp' => $this->timestamp,
            'mediantime' => $this->mediantime,
            'bits' => $this->bits,
            'nonce' => $this->nonce,
            'merkle_root' => $this->merkleRoot,
            'total_transactions' => $this->txCount,
            'size' => $this->size,
            'weight' => $this->weight,
            'difficulty' => $this->difficulty,
            'previous_block_hash' => $this->previousBlockHash,
            'next_block_hash' => $this->nextBlockHash,
            'transactions' => $this->transactions,
        ];
    }
}
