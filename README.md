# dispatch-engine
 
When you submit an order on a major platform, a queue runs in the background — charging cards, reserving stock, sending confirmations. This project builds that queue from scratch, without touching Laravel's built-in queue system.
 
**Built purely for learning.**
 
---
 
## What this is
 
A job queue built on PostgreSQL and Redis. Laravel handles routing and CLI commands — everything else is written from scratch.
 
The project simulates an e-commerce checkout flow across two job types, each exposing a different concurrency problem:
 
```text
User checks out
      │
      ▼
[payment]                — charge the customer
      │                    handler checks payment_records before charging
      ▼
[inventory_reservation]  — reserve stock
                           atomic UPDATE prevents overselling without a separate lock
```
 
---
 
## Key concepts
 
| Component | File | What it solves |
|---|---:|---|
| Schema | `dispatches` migration | Job state machine |
| Atomic claiming | `DispatchClaimer` | No double-processing |
| Failure + backoff | `FailureHandler` | Exponential backoff with jitter |
| Dead-lettering | `status = dead` | Exhausted jobs preserved, not dropped |
| Crash recovery | `WatchdogCommand` | At-least-once delivery |
| API idempotency | `POST /dispatches` | Entry-point deduplication |
| Handler idempotency | `payment_records` check | Safe repeated side effects |
| Inventory reservation | `InventoryHandler` | Atomic conditional updates |
| Rate limiting | `RateLimiter` Lua script | Atomic counters via Redis |
| Load testing | `LoadTest` command | Throughput measurement |
| Dashboard | `/api/stats` + Chart.js | Live queue metrics |
 
---
 
## Architecture
 
```text
                           CLIENTS
                               │
                               ▼
                    ┌─────────────────┐
                    │    HTTP Layer   │
                    │ POST /dispatches│
                    │ GET /stats      │
                    └────────┬────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │ Controllers     │
                    │                 │
                    │ • Validation    │
                    │ • Redis rate    │
                    │   limiter call  │
                    │ • API-level     │
                    │   idempotency   │
                    └────────┬────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │ PostgreSQL      │
                    │ dispatches      │
                    │                 │
                    │ status          │
                    │ payload         │
                    │ attempts        │
                    │ available_at    │
                    │ claimed_by      │
                    │ idempotency_key │
                    └────────┬────────┘
                             │
                             │ FOR UPDATE SKIP LOCKED
                             ▼
                    ┌─────────────────┐
                    │ Worker Pool     │
                    │ n×worker:claim  │
                    └────────┬────────┘
                             │
            ┌────────────────┴────────────────┐
            │                                 │
            ▼                                 ▼
   ┌──────────────────┐             ┌────────────────┐
   │ Job Handlers     │             │ FailureHandler │
   │                  │             │                │
   │ PaymentHandler   │             │ Retry + jitter │
   │ └─ idempotency   │             │ Dead-lettering │
   │                  │             └────────────────┘
   │ InventoryHandler │
   │ └─ atomic stock  │
   └─────────┬────────┘
             │
             ▼
     ┌─────────────────┐
     │ Redis           │
     │                 │
     │ Rate limiter    │
     │ Stats cache     │
     └─────────────────┘
 
                    ┌─────────────────┐
                    │ Watchdog        │
                    │ watchdog:run    │
                    │ resets stuck    │
                    └────────┬────────┘
                             │
                             ▼
                        PostgreSQL
```
 
---
 
## Running it
 
Start individual workers:
 
```bash
php artisan worker:claim worker-1
php artisan worker:claim worker-2
php artisan worker:claim worker-3
```
 
Start a worker pool:
 
```bash
php artisan workerpool:start --workers=4
```
 
Start the watchdog:
 
```bash
php artisan watchdog:run
```
 
Or start everything at once:
 
```bash
php artisan dev:start
```
 
---
 
## How the hard parts work
 
### Atomic claiming
 
`DispatchClaimer` wraps the claim in a transaction using `FOR UPDATE SKIP LOCKED`. Workers compete for the same rows — whichever wins the lock processes the job, the rest skip it without waiting. No application-level coordination needed.
 
### Failure handling
 
`FailureHandler` calculates retry delay as:
 
```
2^attempts + rand(0,3)
```
 
Jitter prevents multiple failed jobs from retrying in sync. After five attempts the job moves to `dead` — preserved for inspection, never silently dropped.
 
### Crash recovery
 
`WatchdogCommand` polls for jobs stuck in `processing` past the timeout window and resets them to `pending`. This gives **at-least-once delivery**: a job that completed its side effect before the worker died will run again. Handlers close that gap by checking before acting.
 
### Idempotency
 
Two layers protect against duplicate processing:
 
**API level** — keyed on `idempotency_key`:
- Same key + same payload → `200 OK`
- Same key + different payload → `409 Conflict`
- New key → `201 Created`
**Handler level** — `PaymentHandler` checks `payment_records` before charging. If a record exists for this dispatch, it returns without charging again.
 
### Inventory reservation
 
```sql
UPDATE products
SET stock = stock - ?
WHERE id = ?
AND stock >= ?
```
 
The check and decrement happen in a single statement — no window between reading stock and writing it.
 
### Rate limiting
 
The rate limiter runs as a Lua script on Redis. Check-and-increment is atomic at the Redis level. Returns `429 Too Many Requests` when the bucket is exhausted.
 
---
 
## Load testing
 
```bash
php artisan loadtest:start --workers=1
php artisan loadtest:start --workers=4
php artisan loadtest:start --workers=8
```
 
| Workers | Jobs | Duration | Throughput |
|---|---:|---:|---:|
| 1 | 1,000 | 1,599s | 38/min |
| 4 | 1,000 | 429s | 140/min |
| 8 | 1,000 | 230s | 252/min |
 
---
 
## Evidence
 
| Claim | Evidence |
|---|---|
| No duplicate claims | 8 concurrent worker logs |
| Stock never negative | Database state after concurrent reservations |
| Crash recovery works | `kill -9` recovery logs |
| Payment handler is idempotent | One `payment_records` row after repeated execution |
| Invalid payloads rejected | `422` before queue insertion |
| Conflicting idempotency rejected | `409` response |
| Rate limiter works | Concurrent Postman tests |
 
In `docs/`.
 
---
 
## Native Laravel vs DIY
 
| Concern | Native Laravel | dispatch-engine |
|---|---|---|
| Atomic claiming | ✅ Internal | ✅ Explicit |
| Retry handling | ✅ Built-in | ✅ `FailureHandler` |
| Dead-lettering | ✅ `failed_jobs` | ✅ `status = dead` |
| Unique jobs | ✅ `ShouldBeUnique` | ✅ Unique index |
| Rate limiting | ❌ | ✅ Redis Lua |
| Claiming visibility | ❌ Black box | ✅ Readable |
 
Full write-up in `docs/native-vs-diy.md`.
 
---
 
_Not a replacement for Laravel Horizon — a codebase designed to make every decision visible._
