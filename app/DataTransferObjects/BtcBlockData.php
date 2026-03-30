<?php

namespace App\DataTransferObjects;

final readonly class BtcBlockData
{
    /**
     * @param  list<string>  $transactions
     */
    public function __construct(
        public string $hash,
        public int $weight,
        public int $height,
        public ?string $miner,
        public int $blockReward,
        public int $totalFees,
        public int $totalTransactions,
        public array $transactions,
        public int $timestamp,
        public int $size,
        public string $difficulty,
        public int $nonce,
        public string $merkleRoot,
    ) {}

    /**
     * @return array{
     *     hash: string,
     *     weight: int,
     *     height: int,
     *     miner: ?string,
     *     block_reward: int,
     *     total_fees: int,
     *     total_transactions: int,
     *     transactions: list<string>,
     *     timestamp: int,
     *     size: int,
     *     difficulty: string,
     *     nonce: int,
     *     merkle_root: string
     * }
     */
    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
            'weight' => $this->weight,
            'height' => $this->height,
            'miner' => $this->miner,
            'block_reward' => $this->blockReward,
            'total_fees' => $this->totalFees,
            'total_transactions' => $this->totalTransactions,
            'transactions' => $this->transactions,
            'timestamp' => $this->timestamp,
            'size' => $this->size,
            'difficulty' => $this->difficulty,
            'nonce' => $this->nonce,
            'merkle_root' => $this->merkleRoot,
        ];
    }
}
