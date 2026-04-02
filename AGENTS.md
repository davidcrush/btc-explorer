# Agent notes — btc-explorer

Context for AI assistants and humans maintaining this repo.

## What this project is

Laravel **13** + **Inertia** + **React 19** + **Chakra UI v3** Bitcoin explorer. All chain data comes from **Blockstream Esplora** (`config('services.blockstream.base_url')`, default `https://blockstream.info/api`). There is **no local full node** — everything is HTTP to Esplora plus **Redis-backed Laravel cache** (store name `BLOCKSTREAM_CACHE_STORE`).

## Architecture

| Concern | Where it lives |
|--------|----------------|
| Upstream HTTP, caching, Esplora quirks | `app/Services/BlockstreamApiClient.php` |
| JSON API | `routes/api.php` → `App\Http\Controllers\Api\V1\*` |
| Inertia pages | `routes/web.php` → `resources/js/Pages/**` |
| API request validation | `app/Http/Requests/Api/V1/*` |
| Serializable API shapes | `app/Http/Resources/Api/V1/*` + readonly DTOs in `app/DataTransferObjects/` |

**Pattern:** Controllers stay thin; the client returns DTOs or arrays; Resources/`toArray()` define JSON. New Esplora-backed features should extend `BlockstreamApiClient` first, then add controller + routes + tests.

## Esplora facts that affect design

- **`GET /mempool/txids`** returns a **JSON array of txid strings only**. No fee/size per row without **`GET /tx/{txid}`**. The mempool list API intentionally **does not** fan out to `/tx/...` (rate limits and latency).
- **`GET /mempool/recent`** returns up to **10** objects with `txid`, `fee`, `vsize`, `value` — **not** paginated; not used for the main mempool table.
- **`GET /mempool`** = backlog stats (`count`, `vsize`, `total_fee`, `fee_histogram`).
- Reference: [Esplora API.md](https://github.com/Blockstream/esplora/blob/master/API.md).

## API routes — ordering matters

In `routes/api.php`, register **static paths before parameterized ones**, e.g.:

- `/btc/transactions/recent` **before** `/btc/transactions/{txid}`
- `/btc/mempool/stats` and `/btc/mempool/transactions` before any future `/btc/mempool/{...}` if added

Same idea in `web.php`: e.g. `/transactions` before `/transactions/{txid}`.

## Caching

- TTLs and store: `config/services.php` (`blockstream.*`).
- Hot vs stable: block **detail** uses longer TTL when `next_block_hash` exists; **mempool** stats + txid list use **hot** TTL (`block_detail_hot_ttl`).
- Client uses versioned cache key prefixes (`btc:blockstream:v…`) — bump or change prefix if cached shape becomes incompatible.

## Frontend

- **Dark-only** UI; layout tokens live in `resources/js/Layouts/AppLayout.jsx` (`palette` constant).
- **Preferences** (amount unit, blocks per page): `resources/js/contexts/UserPreferencesContext.jsx`.
- **Chakra v3** — check existing imports (e.g. `FieldRoot`, `Input`, `IconButton`) before adding new components.
- Pages call **`/api/v1/...`** with **axios** from the browser (same origin).

## Tests

- **Feature tests** under `tests/Feature/` use **`Http::fake`** against the configured Blockstream base URL (usually `https://blockstream.info/api/...`).
- Many tests **`Cache::store(config('services.blockstream.cache_store'))->flush()`** in `setUp()` so cache does not leak between examples.
- When asserting mempool list behavior, ensure **no** unwanted `GET .../tx/{txid}` calls (e.g. `Http::assertNotSent` with a path check).

Run: `composer test` / `php artisan test` / `./vendor/bin/sail artisan test`.

## Style / tooling

- **PHP:** run **`./vendor/bin/pint`** on changed files (or let CI do it).
- Prefer **small, task-scoped diffs**; match existing naming (e.g. `Btc*Controller`, `Btc*Data`, `List*Btc*Request`).

## Docs

- **README.md** — human-oriented setup, env vars, API table, structure.
- Update **README** when adding public API routes or major UI flows.
