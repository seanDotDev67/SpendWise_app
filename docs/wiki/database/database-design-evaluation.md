# Database Design Evaluation

Based on migrations and code review only; no live database or query plans were inspected.

## Strengths
- Clear ownership model (`user_id` on every domain table with FK).
- Money stored as `DECIMAL(12,2)`, not float.
- Sensible unique keys (`categories`, `budgets`, `users`).
- No many-to-many complexity; schema is small and understandable.

## Normalization
- **1NF:** satisfied; all columns are atomic.
- **2NF:** satisfied; all tables have single-column surrogate keys.
- **3NF:** mostly satisfied. `transactions.type` duplicates `categories.type` (denormalized), kept consistent only by controller validation.

## Weaknesses and recommendations

| # | Finding | Impact | Recommendation |
|---|---|---|---|
| 1 | Owner consistency is not enforced: `transactions.user_id` / `budgets.user_id` may differ from `categories.user_id` | Cross-user data leak if a controller check is missed | Composite FK `(category_id, user_id)` to a unique `categories(id, user_id)` |
| 2 | `transactions.type` can disagree with `categories.type` | Wrong totals | Remove the column or add a composite FK/trigger |
| 3 | No index on `transactions(user_id, date)` | Slower reports as data grows (MySQL only indexes the FKs) | Add composite index |
| 4 | Month grouping uses `SUBSTR(date,1,7)` | Cannot use a date index | Use a date range (`>=`, `<`) |
| 5 | `Goal` casts `target`/`saved` to float | Precision loss versus DECIMAL | Cast `decimal:2` |
| 6 | No contributions table for goals | No savings history or audit | Add `goal_contributions` table (future work) |
| 7 | No CHECK constraints (positive amounts, `saved <= target`) | Relies on validation only | Add CHECK constraints (MySQL 8 / TiDB support varies) |
| 8 | `sessions.user_id` has no FK | Orphaned sessions after user delete | Acceptable for sessions; purge on delete if required |
| 9 | CASCADE on user vs RESTRICT on `transactions.category_id` | User deletion may fail on MySQL/TiDB (unverified) | Test on the production engine; delete transactions first or use CASCADE |
| 10 | Missing inverse relationships, typo `transations()` | Maintainability | Add `User::budgets()`, `Budget::user()`, `Goal::user()`; remove the typo alias after checking usages |
| 11 | `categories.color`/`icon` DB default NULL but model default set | Rows inserted outside Eloquent get NULL | Set DB defaults |
| 12 | `jobs` tables are created but unused | Minor clutter | Keep, or switch queue to `sync` |
| 13 | `database` driver for sessions, cache and queue | Extra load on TiDB per request | Consider Redis if traffic grows |

## Security and isolation
Passwords are hashed, emails and Google IDs are unique, and queries are scoped by user in controllers. Isolation depends on application code, not the database (see finding 1).

## Scalability
Fine for personal-finance volumes. Reports aggregate on the fly; add the composite index and date-range filters first. Row-level data is small; session payload growth from AI history is bounded to the last 12 messages.
