# Database Architecture

## Table groups

| Group | Tables | Role |
|---|---|---|
| Identity and authentication | users, password_reset_tokens, sessions | Accounts, credentials, Google link (`google_id`), reset tokens, login sessions |
| Financial core | categories, transactions, budgets | Ledger, classification and spending limits |
| Savings | goals | Targets with a running `saved` amount |
| Framework infrastructure | cache, cache_locks, jobs, job_batches, failed_jobs | Database-backed cache and queue (queue currently unused) |

## User ownership and data isolation
Every domain table carries `user_id` with a foreign key to `users`. Isolation is enforced in the **application layer**: controllers scope queries by the authenticated user (`$request->user()->categories()->findOrFail(...)`, `where('user_id', ...)`, and a 403 check on budgets). The database guarantees that the owner exists, but does not guarantee that related rows share the same owner.

## Financial transactions
A transaction is a positive `DECIMAL(12,2)` amount with a `type` (income/expense), a mandatory category, a date, and optional description, payment method and notes. Balances and totals are not stored. They are computed with `SUM`, `GROUP BY` and `CASE` queries in `ReportDataService`, `AssistantFinancialContext` and the controllers.

## Categories and budgets
A category has a type and an `is_archived` flag. A budget links a user and an expense category to a monthly limit. Spent-versus-limit is computed by summing that category's transactions for the month; nothing is stored.

## Savings
`goals` stores `target`, `saved` and an optional `deadline`. Funding does `increment('saved', amount)`. There is no contributions table, so history is not kept. Projections from `SavingsProjectionService` are calculated on demand and never persisted.

## AI data
No AI-specific tables exist. The assistant reads the user's rows from the tables above, builds context in memory, and keeps conversation history in the session payload (`sessions.payload`). An alert is cached in `cache`. The model (Ollama/Gemini) is external and holds no database state.

## Authentication data
Passwords are hashed in `users.password`. Email/password and Google sign-in share `users` (`google_id`, unique). Sessions are in `sessions`. Link-based reset tokens are in `password_reset_tokens`; the 6-digit code flow uses the cache.

## Referential integrity
- Foreign keys with CASCADE for ownership, RESTRICT for category use.
- Unique keys: `users.email`, `users.google_id`, `categories(user_id,name,type)`, `budgets(user_id,category_id)`, `failed_jobs.uuid`.
- ENUM on both `type` columns.
- Application validation for ranges (amount minimums, lengths).

## Data flow examples
1. **Expense:** form → `TransactionController` validates → category loaded via the user's categories, type compared → insert into `transactions`.
2. **Report:** `ReportDataService` aggregates `transactions` joined to `categories` for the user and month.
3. **Goal question to AI:** `AssistantFinancialContext` reads `goals`, `SavingsProjectionService` computes figures, and the result is passed to the model. Nothing is written.

See [database-workflows.md](./database-workflows.md) for more detail.
