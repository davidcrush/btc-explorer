<?php

namespace App\Services;

use App\DataTransferObjects\BtcBlockData;
use App\DataTransferObjects\BtcBlockDetailData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class BlockstreamApiClient
{
    private const TRANSACTION_PAGE_SIZE = 25;

    /**
     * @return list<BtcBlockData>
     */
    public function latestBlocks(int $limit = 10): array
    {
        $safeLimit = max(1, min($limit, 100));

        try {
            $cachedBlocks = Cache::store($this->cacheStore())->remember(
                $this->latestBlocksCacheKey($safeLimit),
                now()->addSeconds($this->cacheTtl()),
                fn (): array => $this->fetchLatestBlocksPayload($safeLimit),
            );
        } catch (Throwable) {
            // If cache is unavailable, still serve data directly from upstream.
            $cachedBlocks = $this->fetchLatestBlocksPayload($safeLimit);
        }

        return $this->hydrateBlocks($cachedBlocks);
    }

    public function blockDetails(string $hash, int $transactionsStart = 0, int $transactionsLimit = self::TRANSACTION_PAGE_SIZE): ?BtcBlockDetailData
    {
        $normalizedStart = $this->normalizeTransactionsStart($transactionsStart);
        $normalizedLimit = $this->normalizeTransactionsLimit($transactionsLimit);
        $cacheKey = $this->blockDetailsCacheKey($hash, $normalizedStart, $normalizedLimit);
        $cacheStore = Cache::store($this->cacheStore());

        try {
            $cachedPayload = $cacheStore->get($cacheKey);

            if (is_array($cachedPayload)) {
                return $this->hydrateBlockDetail($cachedPayload);
            }
        } catch (Throwable) {
            // Ignore cache read failures and continue with upstream fetch.
        }

        $payload = $this->fetchBlockDetailsPayload($hash, $normalizedStart, $normalizedLimit);

        if (is_array($payload)) {
            try {
                $cacheStore->put(
                    $cacheKey,
                    $payload,
                    now()->addSeconds($this->blockDetailsTtl($payload)),
                );
            } catch (Throwable) {
                // Ignore cache write failures.
            }
        }

        return $this->hydrateBlockDetail($payload);
    }

    /**
     * @param  mixed  $payload
     */
    private function hydrateBlockDetail(mixed $payload): ?BtcBlockDetailData
    {
        if (! is_array($payload)) {
            return null;
        }

        $transactions = $payload['transactions'] ?? [];

        if (! is_array($transactions)) {
            $transactions = [];
        }

        return new BtcBlockDetailData(
            hash: (string) ($payload['hash'] ?? ''),
            height: (int) ($payload['height'] ?? 0),
            version: (int) ($payload['version'] ?? 0),
            timestamp: (int) ($payload['timestamp'] ?? 0),
            mediantime: (int) ($payload['mediantime'] ?? 0),
            bits: (string) ($payload['bits'] ?? ''),
            nonce: (int) ($payload['nonce'] ?? 0),
            merkleRoot: (string) ($payload['merkle_root'] ?? ''),
            txCount: (int) ($payload['total_transactions'] ?? 0),
            size: (int) ($payload['size'] ?? 0),
            weight: (int) ($payload['weight'] ?? 0),
            difficulty: (string) ($payload['difficulty'] ?? '0'),
            previousBlockHash: $this->nullableString($payload['previous_block_hash'] ?? null),
            nextBlockHash: $this->nullableString($payload['next_block_hash'] ?? null),
            transactionsStart: (int) ($payload['transactions_start'] ?? 0),
            transactionsLimit: (int) ($payload['transactions_limit'] ?? self::TRANSACTION_PAGE_SIZE),
            hasMoreTransactions: (bool) ($payload['has_more_transactions'] ?? false),
            nextTransactionsStart: isset($payload['next_transactions_start']) ? (int) $payload['next_transactions_start'] : null,
            transactions: array_values(array_filter($transactions, static fn (mixed $txid): bool => is_string($txid))),
        );
    }

    /**
     * @return list<array{
     *     hash: string,
     *     weight: int,
     *     height: int,
     *     total_transactions: int,
     *     transactions: list<string>,
     *     timestamp: int,
     *     size: int,
     *     difficulty: string,
     *     nonce: int,
     *     merkle_root: string
     * }>
     */
    private function fetchLatestBlocksPayload(int $safeLimit): array
    {

        $blocksResponse = Http::baseUrl($this->baseUrl())
            ->timeout($this->timeout())
            ->acceptJson()
            ->get('/blocks');

        if (! $blocksResponse->successful()) {
            throw new RuntimeException('Unable to fetch latest blocks from Blockstream.');
        }

        $blocks = $blocksResponse->json();

        if (! is_array($blocks)) {
            throw new RuntimeException('Unexpected Blockstream blocks response format.');
        }

        $mapped = [];

        foreach (array_slice($blocks, 0, $safeLimit) as $block) {
            if (! is_array($block) || ! isset($block['id']) || ! is_string($block['id'])) {
                continue;
            }

            $mapped[] = [
                'hash' => (string) $block['id'],
                'weight' => (int) ($block['weight'] ?? 0),
                'height' => (int) ($block['height'] ?? 0),
                'total_transactions' => (int) ($block['tx_count'] ?? 0),
                'transactions' => $this->fetchTransactionIdsPage((string) $block['id'], 0, self::TRANSACTION_PAGE_SIZE),
                'timestamp' => (int) ($block['timestamp'] ?? 0),
                'size' => (int) ($block['size'] ?? 0),
                'difficulty' => (string) ($block['difficulty'] ?? '0'),
                'nonce' => (int) ($block['nonce'] ?? 0),
                'merkle_root' => (string) ($block['merkle_root'] ?? ''),
            ];
        }

        return $mapped;
    }

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
     *     transactions_start: int,
     *     transactions_limit: int,
     *     has_more_transactions: bool,
     *     next_transactions_start: ?int,
     *     transactions: list<string>
     * }|null
     */
    private function fetchBlockDetailsPayload(string $hash, int $transactionsStart, int $transactionsLimit): ?array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->timeout($this->timeout())
            ->acceptJson()
            ->get("/block/{$hash}");

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw new RuntimeException('Unable to fetch block details from Blockstream.');
        }

        $block = $response->json();

        if (! is_array($block)) {
            throw new RuntimeException('Unexpected Blockstream block details format.');
        }

        $height = (int) ($block['height'] ?? 0);
        $transactions = $this->fetchTransactionIdsPage((string) ($block['id'] ?? ''), $transactionsStart, $transactionsLimit);
        $totalTransactions = (int) ($block['tx_count'] ?? 0);
        $nextTransactionsStart = ($transactionsStart + count($transactions)) < $totalTransactions
            ? ($transactionsStart + count($transactions))
            : null;

        return [
            'hash' => (string) ($block['id'] ?? ''),
            'height' => $height,
            'version' => (int) ($block['version'] ?? 0),
            'timestamp' => (int) ($block['timestamp'] ?? 0),
            'mediantime' => (int) ($block['mediantime'] ?? 0),
            'bits' => (string) ($block['bits'] ?? ''),
            'nonce' => (int) ($block['nonce'] ?? 0),
            'merkle_root' => (string) ($block['merkle_root'] ?? ''),
            'total_transactions' => $totalTransactions,
            'size' => (int) ($block['size'] ?? 0),
            'weight' => (int) ($block['weight'] ?? 0),
            'difficulty' => (string) ($block['difficulty'] ?? '0'),
            'previous_block_hash' => $this->nullableString($block['previousblockhash'] ?? null),
            'next_block_hash' => $this->nextBlockHash($height),
            'transactions_start' => $transactionsStart,
            'transactions_limit' => $transactionsLimit,
            'has_more_transactions' => $nextTransactionsStart !== null,
            'next_transactions_start' => $nextTransactionsStart,
            'transactions' => $transactions,
        ];
    }

    /**
     * @param  mixed  $cachedBlocks
     * @return list<BtcBlockData>
     */
    private function hydrateBlocks(mixed $cachedBlocks): array
    {
        if (! is_array($cachedBlocks)) {
            return [];
        }

        $blocks = [];

        foreach ($cachedBlocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $transactions = $block['transactions'] ?? [];

            if (! is_array($transactions)) {
                $transactions = [];
            }

            $blocks[] = new BtcBlockData(
                hash: (string) ($block['hash'] ?? ''),
                weight: (int) ($block['weight'] ?? 0),
                height: (int) ($block['height'] ?? 0),
                totalTransactions: (int) ($block['total_transactions'] ?? count($transactions)),
                transactions: array_values(array_filter($transactions, static fn (mixed $txid): bool => is_string($txid))),
                timestamp: (int) ($block['timestamp'] ?? 0),
                size: (int) ($block['size'] ?? 0),
                difficulty: (string) ($block['difficulty'] ?? '0'),
                nonce: (int) ($block['nonce'] ?? 0),
                merkleRoot: (string) ($block['merkle_root'] ?? ''),
            );
        }

        return $blocks;
    }

    /**
     * @return list<string>
     */
    private function fetchTransactionIdsPage(string $blockHash, int $transactionsStart, int $transactionsLimit): array
    {
        if ($blockHash === '') {
            return [];
        }

        $start = $this->normalizeTransactionsStart($transactionsStart);
        $limit = $this->normalizeTransactionsLimit($transactionsLimit);
        $endpoint = $start === 0
            ? "/block/{$blockHash}/txs"
            : "/block/{$blockHash}/txs/{$start}";

        $response = Http::baseUrl($this->baseUrl())
            ->timeout($this->timeout())
            ->acceptJson()
            ->get($endpoint);

        if (! $response->successful()) {
            return [];
        }

        $transactions = $response->json();

        if (! is_array($transactions)) {
            return [];
        }

        $txids = [];

        foreach ($transactions as $transaction) {
            if (! is_array($transaction)) {
                continue;
            }

            $txid = $transaction['txid'] ?? null;

            if (is_string($txid) && $txid !== '') {
                $txids[] = $txid;
            }
        }

        return array_slice($txids, 0, $limit);
    }

    private function baseUrl(): string
    {
        return (string) config('services.blockstream.base_url', 'https://blockstream.info/api');
    }

    private function timeout(): int
    {
        return (int) config('services.blockstream.timeout', 10);
    }

    private function cacheStore(): string
    {
        return (string) config('services.blockstream.cache_store', 'redis');
    }

    private function cacheTtl(): int
    {
        return (int) config('services.blockstream.cache_ttl', 30);
    }

    private function latestBlocksCacheKey(int $limit): string
    {
        return "btc:blockstream:v3:latest-blocks:limit:{$limit}";
    }

    private function blockDetailsCacheKey(string $hash, int $transactionsStart, int $transactionsLimit): string
    {
        return "btc:blockstream:v3:block-details:{$hash}:start:{$transactionsStart}:limit:{$transactionsLimit}";
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function blockDetailsTtl(array $payload): int
    {
        return $this->nullableString($payload['next_block_hash'] ?? null) !== null
            ? $this->blockDetailStableTtl()
            : $this->blockDetailHotTtl();
    }

    private function blockDetailHotTtl(): int
    {
        return (int) config('services.blockstream.block_detail_hot_ttl', $this->cacheTtl());
    }

    private function blockDetailStableTtl(): int
    {
        return (int) config('services.blockstream.block_detail_stable_ttl', 86400);
    }

    private function nextBlockHash(int $height): ?string
    {
        $response = Http::baseUrl($this->baseUrl())
            ->timeout($this->timeout())
            ->acceptJson()
            ->get('/block-height/'.($height + 1));

        if (! $response->successful()) {
            return null;
        }

        $hash = trim((string) $response->body());

        return $hash !== '' ? $hash : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeTransactionsStart(int $transactionsStart): int
    {
        if ($transactionsStart < 0) {
            return 0;
        }

        return intdiv($transactionsStart, self::TRANSACTION_PAGE_SIZE) * self::TRANSACTION_PAGE_SIZE;
    }

    private function normalizeTransactionsLimit(int $transactionsLimit): int
    {
        if ($transactionsLimit < 1) {
            return self::TRANSACTION_PAGE_SIZE;
        }

        return min($transactionsLimit, self::TRANSACTION_PAGE_SIZE);
    }
}
