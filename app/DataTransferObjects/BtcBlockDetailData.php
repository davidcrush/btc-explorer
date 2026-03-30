<?php

namespace App\DataTransferObjects;

final readonly class BtcBlockDetailData
{
    /**
     * @param  list<array{
     *     txid: string,
     *     is_coinbase: bool,
     *     fee: int,
     *     input_total: int,
     *     output_total: int,
     *     inputs: list<array{txid: ?string, vout: ?int, address: ?string, value: int, is_coinbase: bool}>,
     *     outputs: list<array{address: ?string, value: int}>
     * }>  $transactions
     */
    public function __construct(
        public string $hash,
        public int $height,
        public int $version,
        public int $timestamp,
        public int $mediantime,
        public ?string $miner,
        public string $bits,
        public int $nonce,
        public string $merkleRoot,
        public int $blockReward,
        public int $totalFees,
        public int $txCount,
        public int $size,
        public int $weight,
        public string $difficulty,
        public ?string $previousBlockHash,
        public ?string $nextBlockHash,
        public int $transactionsStart,
        public int $transactionsLimit,
        public bool $hasMoreTransactions,
        public ?int $nextTransactionsStart,
        public array $transactions,
    ) {}

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
    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
            'height' => $this->height,
            'version' => $this->version,
            'timestamp' => $this->timestamp,
            'mediantime' => $this->mediantime,
            'miner' => $this->miner,
            'bits' => $this->bits,
            'nonce' => $this->nonce,
            'merkle_root' => $this->merkleRoot,
            'block_reward' => $this->blockReward,
            'total_fees' => $this->totalFees,
            'total_transactions' => $this->txCount,
            'size' => $this->size,
            'weight' => $this->weight,
            'difficulty' => $this->difficulty,
            'previous_block_hash' => $this->previousBlockHash,
            'next_block_hash' => $this->nextBlockHash,
            'transactions_start' => $this->transactionsStart,
            'transactions_limit' => $this->transactionsLimit,
            'has_more_transactions' => $this->hasMoreTransactions,
            'next_transactions_start' => $this->nextTransactionsStart,
            'transactions' => $this->transactions,
        ];
    }
}
