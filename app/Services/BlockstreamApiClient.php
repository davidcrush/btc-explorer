<?php

namespace App\Services;

use App\DataTransferObjects\BtcBlockData;
use App\DataTransferObjects\BtcBlockDetailData;
use App\DataTransferObjects\BtcTransactionDetailData;
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
    public function latestBlocks(int $limit = 10, int $offset = 0): array
    {
        $safeLimit = max(1, min($limit, 50));
        $safeOffset = max(0, min($offset, 500));
        $store = Cache::store($this->cacheStore());
        $key = $this->latestBlocksCacheKey($safeLimit, $safeOffset);
        $ttl = now()->addSeconds($this->cacheTtl());

        try {
            $cached = $store->get($key);

            if (is_array($cached) && $cached !== []) {
                return $this->hydrateBlocks($cached);
            }
        } catch (Throwable) {
            // Ignore cache read errors and fetch from upstream.
        }

        $payload = $this->fetchLatestBlocksPayload($safeLimit, $safeOffset);

        if ($payload !== []) {
            try {
                $store->put($key, $payload, $ttl);
            } catch (Throwable) {
                // Ignore cache write errors.
            }
        }

        return $this->hydrateBlocks($payload);
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

    public function transactionDetails(string $txid): ?BtcTransactionDetailData
    {
        $normalized = $this->normalizeTxid($txid);
        if ($normalized === null) {
            return null;
        }

        $store = Cache::store($this->cacheStore());
        $key = $this->transactionDetailsCacheKey($normalized);

        try {
            $cached = $store->get($key);
            if (is_array($cached) && ($cached['txid'] ?? '') !== '') {
                return $this->hydrateTransactionDetail($cached);
            }
        } catch (Throwable) {
            // Ignore cache read failures.
        }

        $payload = $this->fetchTransactionDetailPayload($normalized);

        if ($payload === null) {
            return null;
        }

        try {
            $store->put(
                $key,
                $payload,
                now()->addSeconds($this->transactionDetailTtlByPayload($payload)),
            );
        } catch (Throwable) {
            // Ignore cache write failures.
        }

        return $this->hydrateTransactionDetail($payload);
    }

    private function hydrateTransactionDetail(mixed $payload): ?BtcTransactionDetailData
    {
        if (! is_array($payload) || ($payload['txid'] ?? '') === '') {
            return null;
        }

        return new BtcTransactionDetailData(
            txid: (string) $payload['txid'],
            confirmed: (bool) ($payload['confirmed'] ?? false),
            blockHash: $this->nullableString($payload['block_hash'] ?? null),
            blockHeight: isset($payload['block_height']) ? (int) $payload['block_height'] : null,
            fee: (int) ($payload['fee'] ?? 0),
            size: (int) ($payload['size'] ?? 0),
            weight: (int) ($payload['weight'] ?? 0),
            virtualSize: (int) ($payload['virtual_size'] ?? 0),
            isCoinbase: (bool) ($payload['is_coinbase'] ?? false),
            inputTotal: (int) ($payload['input_total'] ?? 0),
            outputTotal: (int) ($payload['output_total'] ?? 0),
            inputs: is_array($payload['inputs'] ?? null) ? $payload['inputs'] : [],
            outputs: is_array($payload['outputs'] ?? null) ? $payload['outputs'] : [],
        );
    }

    private function normalizeTxid(string $txid): ?string
    {
        $normalized = strtolower(trim($txid));

        if (preg_match('/^[a-f0-9]{64}$/', $normalized) !== 1) {
            return null;
        }

        return $normalized;
    }

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
            miner: $this->nullableString($payload['miner'] ?? null),
            bits: (string) ($payload['bits'] ?? ''),
            nonce: (int) ($payload['nonce'] ?? 0),
            merkleRoot: (string) ($payload['merkle_root'] ?? ''),
            blockReward: (int) ($payload['block_reward'] ?? 0),
            totalFees: (int) ($payload['total_fees'] ?? 0),
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
            transactions: array_values(array_filter($transactions, static fn (mixed $tx): bool => is_array($tx))),
        );
    }

    /**
     * Esplora returns at most 10 blocks per `GET /blocks` or `GET /blocks/:start_height`.
     * Larger limits are satisfied by paging using the previous page's lowest height minus one.
     * `$safeOffset` skips that many blocks from the chain tip before collecting.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchLatestBlocksPayload(int $safeLimit, int $safeOffset = 0): array
    {
        $mapped = [];
        $nextStartHeight = null;
        $skipRemaining = $safeOffset;

        while (count($mapped) < $safeLimit) {
            $path = $nextStartHeight === null ? '/blocks' : '/blocks/'.$nextStartHeight;

            $blocksResponse = Http::baseUrl($this->baseUrl())
                ->timeout($this->timeout())
                ->acceptJson()
                ->get($path);

            if (! $blocksResponse->successful()) {
                throw new RuntimeException(sprintf(
                    'Blockstream request failed for %s with HTTP %d.',
                    $path,
                    $blocksResponse->status()
                ));
            }

            $blocks = $blocksResponse->json();

            if (! is_array($blocks)) {
                throw new RuntimeException('Blockstream blocks response is not a JSON array.');
            }

            if ($blocks === []) {
                if ($nextStartHeight === null) {
                    throw new RuntimeException('Blockstream returned an empty block list at the chain tip.');
                }

                break;
            }

            $page = array_slice($blocks, 0, 10);

            foreach ($page as $block) {
                if (! is_array($block)) {
                    continue;
                }

                $blockHash = null;

                if (isset($block['id']) && is_string($block['id']) && $block['id'] !== '') {
                    $blockHash = $block['id'];
                } elseif (isset($block['hash']) && is_string($block['hash']) && $block['hash'] !== '') {
                    $blockHash = $block['hash'];
                }

                if ($blockHash === null) {
                    continue;
                }

                if ($skipRemaining > 0) {
                    $skipRemaining--;

                    continue;
                }

                $height = (int) ($block['height'] ?? 0);
                $economics = $this->fetchBlockEconomics($blockHash, $height);

                $mapped[] = [
                    'hash' => $blockHash,
                    'weight' => (int) ($block['weight'] ?? 0),
                    'height' => $height,
                    'miner' => $economics['miner'],
                    'block_reward' => $economics['block_reward'],
                    'total_fees' => $economics['total_fees'],
                    'total_transactions' => (int) ($block['tx_count'] ?? 0),
                    'transactions' => $this->fetchTransactionIdsPage($blockHash, 0, self::TRANSACTION_PAGE_SIZE),
                    'timestamp' => (int) ($block['timestamp'] ?? 0),
                    'size' => (int) ($block['size'] ?? 0),
                    'difficulty' => (string) ($block['difficulty'] ?? '0'),
                    'nonce' => (int) ($block['nonce'] ?? 0),
                    'merkle_root' => (string) ($block['merkle_root'] ?? ''),
                ];

                if (count($mapped) >= $safeLimit) {
                    break 2;
                }
            }

            if (count($page) < 10) {
                break;
            }

            $heights = [];

            foreach ($page as $b) {
                if (is_array($b)) {
                    $heights[] = (int) ($b['height'] ?? 0);
                }
            }

            if ($heights === []) {
                break;
            }

            $minHeight = min($heights);
            $nextStartHeight = $minHeight - 1;

            if ($nextStartHeight < 0) {
                break;
            }
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
        $economics = $this->fetchBlockEconomics((string) ($block['id'] ?? ''), $height);
        $transactions = $this->fetchTransactionOverviewsPage((string) ($block['id'] ?? ''), $transactionsStart, $transactionsLimit);
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
            'miner' => $economics['miner'],
            'bits' => (string) ($block['bits'] ?? ''),
            'nonce' => (int) ($block['nonce'] ?? 0),
            'merkle_root' => (string) ($block['merkle_root'] ?? ''),
            'block_reward' => $economics['block_reward'],
            'total_fees' => $economics['total_fees'],
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
                miner: $this->nullableString($block['miner'] ?? null),
                blockReward: (int) ($block['block_reward'] ?? 0),
                totalFees: (int) ($block['total_fees'] ?? 0),
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
        $transactions = $this->fetchTransactionOverviewsPage($blockHash, $transactionsStart, $transactionsLimit);

        $txids = [];

        foreach ($transactions as $transaction) {
            $txid = $transaction['txid'] ?? null;

            if (is_string($txid) && $txid !== '') {
                $txids[] = $txid;
            }
        }

        return $txids;
    }

    /**
     * @return list<array{
     *     txid: string,
     *     is_coinbase: bool,
     *     fee: int,
     *     input_total: int,
     *     output_total: int,
     *     inputs: list<array{txid: ?string, vout: ?int, address: ?string, value: int, is_coinbase: bool}>,
     *     outputs: list<array{address: ?string, value: int}>
     * }>
     */
    private function fetchTransactionOverviewsPage(string $blockHash, int $transactionsStart, int $transactionsLimit): array
    {
        if ($blockHash === '') {
            return [];
        }

        $start = $this->normalizeTransactionsStart($transactionsStart);
        $limit = $this->normalizeTransactionsLimit($transactionsLimit);
        $transactions = $this->fetchBlockTransactionsPageRaw($blockHash, $start);

        $txs = [];
        $order = 0;

        foreach ($transactions as $transaction) {
            if (! is_array($transaction)) {
                continue;
            }

            $txid = $transaction['txid'] ?? null;

            if (! is_string($txid) || $txid === '') {
                continue;
            }

            $inputs = $this->mapInputs($transaction['vin'] ?? []);
            $outputs = $this->mapOutputs($transaction['vout'] ?? []);
            $isCoinbase = $this->isCoinbaseTx($inputs);

            $inputTotal = 0;
            foreach ($inputs as $input) {
                $inputTotal += $input['value'];
            }

            $outputTotal = 0;
            foreach ($outputs as $output) {
                $outputTotal += $output['value'];
            }

            $txs[] = [
                'txid' => $txid,
                'is_coinbase' => $isCoinbase,
                'fee' => (int) ($transaction['fee'] ?? 0),
                'input_total' => $isCoinbase ? 0 : $inputTotal,
                'output_total' => $outputTotal,
                'inputs' => $inputs,
                'outputs' => $outputs,
                '_order' => $order,
            ];
            $order++;
        }

        usort($txs, static function (array $a, array $b): int {
            if ($a['is_coinbase'] === $b['is_coinbase']) {
                return $a['_order'] <=> $b['_order'];
            }

            return $a['is_coinbase'] ? -1 : 1;
        });

        $sliced = array_slice($txs, 0, $limit);

        foreach ($sliced as &$tx) {
            unset($tx['_order']);
        }
        unset($tx);

        return $sliced;
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
        return (int) config('services.blockstream.cache_ttl', 90);
    }

    private function latestBlocksCacheKey(int $limit, int $offset = 0): string
    {
        return "btc:blockstream:v7:latest-blocks:limit:{$limit}:offset:{$offset}";
    }

    private function blockDetailsCacheKey(string $hash, int $transactionsStart, int $transactionsLimit): string
    {
        return "btc:blockstream:v4:block-details:{$hash}:start:{$transactionsStart}:limit:{$transactionsLimit}";
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

    private function transactionDetailsCacheKey(string $txid): string
    {
        return "btc:blockstream:v1:tx-detail:{$txid}";
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function transactionDetailTtlByPayload(array $payload): int
    {
        return ($payload['confirmed'] ?? false) === true
            ? $this->blockDetailStableTtl()
            : $this->blockDetailHotTtl();
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

    private function detectMiner(string $blockHash): ?string
    {
        $transactions = $this->fetchBlockTransactionsPageRaw($blockHash, 0);
        $coinbaseTx = $transactions[0] ?? null;

        if (! is_array($coinbaseTx)) {
            return null;
        }

        return $this->detectMinerFromCoinbaseTransaction($coinbaseTx);
    }

    /**
     * @param  array<string, mixed>  $coinbaseTx
     */
    private function detectMinerFromCoinbaseTransaction(array $coinbaseTx): ?string
    {
        $vin = $coinbaseTx['vin'] ?? [];

        if (is_array($vin) && isset($vin[0]) && is_array($vin[0])) {
            $tag = $this->extractCoinbaseTag($vin[0]['scriptsig'] ?? null);

            if ($tag !== null) {
                return $tag;
            }
        }

        $vout = $coinbaseTx['vout'] ?? [];

        if (is_array($vout)) {
            foreach ($vout as $output) {
                if (! is_array($output)) {
                    continue;
                }

                $address = $output['scriptpubkey_address'] ?? null;

                if (is_string($address) && $address !== '') {
                    return $address;
                }
            }
        }

        return null;
    }

    /**
     * @return array{miner: ?string, block_reward: int, total_fees: int}
     */
    private function fetchBlockEconomics(string $blockHash, int $height): array
    {
        $transactions = $this->fetchBlockTransactionsPageRaw($blockHash, 0);
        $coinbaseTx = $transactions[0] ?? null;

        if (! is_array($coinbaseTx)) {
            return [
                'miner' => null,
                'block_reward' => 0,
                'total_fees' => 0,
            ];
        }

        $miner = $this->detectMinerFromCoinbaseTransaction($coinbaseTx);
        $blockReward = $this->coinbaseOutputTotal($coinbaseTx);
        $subsidy = $this->blockSubsidySats($height);
        $totalFees = max(0, $blockReward - $subsidy);

        return [
            'miner' => $miner,
            'block_reward' => $blockReward,
            'total_fees' => $totalFees,
        ];
    }

    private function blockSubsidySats(int $height): int
    {
        if ($height < 0) {
            return 0;
        }

        $halvings = intdiv($height, 210000);

        if ($halvings >= 64) {
            return 0;
        }

        return intdiv(5_000_000_000, 2 ** $halvings);
    }

    /**
     * @param  array<string, mixed>  $coinbaseTx
     */
    private function coinbaseOutputTotal(array $coinbaseTx): int
    {
        $vout = $coinbaseTx['vout'] ?? [];

        if (! is_array($vout)) {
            return 0;
        }

        $total = 0;

        foreach ($vout as $output) {
            if (! is_array($output)) {
                continue;
            }

            $total += (int) ($output['value'] ?? 0);
        }

        return $total;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchBlockTransactionsPageRaw(string $blockHash, int $transactionsStart): array
    {
        if ($blockHash === '') {
            return [];
        }

        $start = $this->normalizeTransactionsStart($transactionsStart);
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

        return $transactions;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchTransactionDetailPayload(string $txid): ?array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->timeout($this->timeout())
            ->acceptJson()
            ->get('/tx/'.$txid);

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'Blockstream transaction request failed with HTTP %d.',
                $response->status()
            ));
        }

        $tx = $response->json();

        if (! is_array($tx)) {
            throw new RuntimeException('Unexpected Blockstream transaction response format.');
        }

        return $this->buildTransactionDetailArray($tx);
    }

    /**
     * @param  array<string, mixed>  $tx
     * @return array<string, mixed>
     */
    private function buildTransactionDetailArray(array $tx): array
    {
        $txid = strtolower((string) ($tx['txid'] ?? ''));
        $inputs = $this->mapInputs($tx['vin'] ?? []);
        $outputs = $this->mapOutputs($tx['vout'] ?? []);
        $isCoinbase = $this->isCoinbaseTx($inputs);

        $inputTotal = 0;
        foreach ($inputs as $input) {
            $inputTotal += $input['value'];
        }

        $outputTotal = 0;
        foreach ($outputs as $output) {
            $outputTotal += $output['value'];
        }

        $size = (int) ($tx['size'] ?? 0);
        $weight = (int) ($tx['weight'] ?? 0);
        $virtualSize = $weight > 0 ? intdiv($weight + 3, 4) : $size;

        $status = $tx['status'] ?? [];
        $confirmed = is_array($status) && (($status['confirmed'] ?? false) === true);
        $blockHash = null;
        $blockHeight = null;

        if (is_array($status)) {
            $blockHash = $this->nullableString($status['block_hash'] ?? null);
            if (isset($status['block_height'])) {
                $blockHeight = (int) $status['block_height'];
            }
        }

        return [
            'txid' => $txid,
            'confirmed' => $confirmed,
            'block_hash' => $blockHash,
            'block_height' => $blockHeight,
            'fee' => (int) ($tx['fee'] ?? 0),
            'size' => $size,
            'weight' => $weight,
            'virtual_size' => $virtualSize,
            'is_coinbase' => $isCoinbase,
            'input_total' => $isCoinbase ? 0 : $inputTotal,
            'output_total' => $outputTotal,
            'inputs' => $inputs,
            'outputs' => $outputs,
        ];
    }

    private function extractCoinbaseTag(mixed $scriptSig): ?string
    {
        if (! is_string($scriptSig) || $scriptSig === '') {
            return null;
        }

        $decoded = @hex2bin($scriptSig);

        if (! is_string($decoded) || $decoded === '') {
            return null;
        }

        if (preg_match('/\/([A-Za-z0-9 .:_-]{2,64})\//', $decoded, $matches) === 1) {
            $tag = $this->sanitizeMinerText($matches[1]);

            if ($tag !== null) {
                return $tag;
            }
        }

        if (preg_match_all('/[A-Za-z0-9 .:_-]{4,}/', $decoded, $matches) > 0) {
            foreach ($matches[0] as $candidate) {
                $trimmed = $this->sanitizeMinerText($candidate);

                if ($trimmed !== null) {
                    return $trimmed;
                }
            }
        }

        return null;
    }

    private function sanitizeMinerText(string $value): ?string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (preg_match('//u', $trimmed) !== 1) {
            return null;
        }

        $clean = preg_replace('/[^\x20-\x7E]/u', '', $trimmed);

        if (! is_string($clean)) {
            return null;
        }

        $clean = trim($clean);

        return $clean === '' ? null : $clean;
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

    /**
     * @return list<array{txid: ?string, vout: ?int, address: ?string, value: int, is_coinbase: bool}>
     */
    private function mapInputs(mixed $vin): array
    {
        if (! is_array($vin)) {
            return [];
        }

        $inputs = [];

        foreach ($vin as $input) {
            if (! is_array($input)) {
                continue;
            }

            $prevout = $input['prevout'] ?? null;
            $address = null;
            $value = 0;

            if (is_array($prevout)) {
                $addressValue = $prevout['scriptpubkey_address'] ?? null;
                $address = is_string($addressValue) && $addressValue !== '' ? $addressValue : null;
                $value = (int) ($prevout['value'] ?? 0);
            }

            $inputs[] = [
                'txid' => is_string($input['txid'] ?? null) ? $input['txid'] : null,
                'vout' => isset($input['vout']) ? (int) $input['vout'] : null,
                'address' => $address,
                'value' => $value,
                'is_coinbase' => (bool) ($input['is_coinbase'] ?? false),
            ];
        }

        return $inputs;
    }

    /**
     * @return list<array{address: ?string, value: int}>
     */
    private function mapOutputs(mixed $vout): array
    {
        if (! is_array($vout)) {
            return [];
        }

        $outputs = [];

        foreach ($vout as $output) {
            if (! is_array($output)) {
                continue;
            }

            $addressValue = $output['scriptpubkey_address'] ?? null;

            $outputs[] = [
                'address' => is_string($addressValue) && $addressValue !== '' ? $addressValue : null,
                'value' => (int) ($output['value'] ?? 0),
            ];
        }

        return $outputs;
    }

    /**
     * @param  list<array{is_coinbase: bool}>  $inputs
     */
    private function isCoinbaseTx(array $inputs): bool
    {
        foreach ($inputs as $input) {
            if (($input['is_coinbase'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }
}
