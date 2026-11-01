# Database Relationships

Only relationships that exist in the migrations or models are listed. There are no many-to-many relationships, pivot tables or soft deletes.

## Summary

| # | Parent | Child | Type | FK | On delete | Enforced by |
|---|---|---|---|---|---|---|
| 1 | users | categories | 1:N | categories.user_id | CASCADE | Database FK |
| 2 | users | transactions | 1:N | transactions.user_id | CASCADE | Database FK |
| 3 | users | budgets | 1:N | budgets.user_id | CASCADE | Database FK |
| 4 | users | goals | 1:N | goals.user_id | CASCADE | Database FK |
| 5 | categories | transactions | 1:N | transactions.category_id | RESTRICT | Database FK |
| 6 | categories | budgets | 1:0..1 | budgets.category_id | CASCADE | Database FK + unique (user_id, category_id) |
| 7 | users | sessions | 1:N (logical) | sessions.user_id | none | Not enforced |
| 8 | users | password_reset_tokens | 1:0..1 (logical) | email | none | Not enforced |

## 1. users → categories
- **Eloquent:** `User::categories()` hasMany; `Category::user()` belongsTo.
- **Purpose:** each user keeps a private category list. The unique key `(user_id, name, type)` allows "Other" as both income and expense, but not twice.
- **Integrity:** deleting a user deletes the categories (subject to relationship 5).
- **Example:** user 7 creates the expense category "Food"; `categories.user_id = 7`.

## 2. users → transactions
- **Eloquent:** `User::transactions()` hasMany (plus a misspelled alias `transations()`); `Transaction::user()` belongsTo.
- **Purpose:** ownership and data isolation. Controllers query through `$request->user()->transactions()`.
- **Integrity:** deleting a user deletes the transactions.

## 3. users → budgets
- **Eloquent:** none. `Budget` has no `user()` and `User` has no `budgets()`; budgets are queried with `where('user_id', …)`.
- **Purpose:** a user's monthly limits.
- **Integrity:** CASCADE on user delete.

## 4. users → goals
- **Eloquent:** `User::goals()` hasMany. `Goal` has no `user()`.
- **Purpose:** savings goals owned by a user.
- **Integrity:** CASCADE on user delete.

## 5. categories → transactions
- **Eloquent:** `Category::transactions()` hasMany; `Transaction::category()` belongsTo.
- **Purpose:** classifies each transaction; reports group by category.
- **Integrity:** `RESTRICT`: a category in use cannot be deleted. `CategoryController::destroy` also checks this and refuses. The category's `type` must match `transactions.type`; this is checked only in `TransactionController`, not by the database.
- **Example:** transaction "Lunch" (expense, 150.00) references category "Food" (expense).

## 6. categories → budgets
- **Eloquent:** `Budget::category()` belongsTo. `Category` has no `budget()` method.
- **Purpose:** an optional monthly limit for a category. The unique key makes it at most one per user and category.
- **Integrity:** CASCADE: deleting a category removes its budget. `BudgetController` verifies the category belongs to the user on store, update and destroy; the database does not check that `budgets.user_id` equals `categories.user_id`.

## 7. users → sessions (logical)
`sessions.user_id` is nullable and indexed with no foreign key, so guests have `NULL`. Deleting a user does not remove session rows; they expire by `last_activity`.

## 8. users → password_reset_tokens (logical)
Linked by `email` value only. No FK. The 6-digit reset flow does not use this table (it uses the cache).

## Delete behaviour when a user account is deleted
`ProfileController::destroy` deletes the user. The database cascades to the four child tables. `transactions.category_id` is RESTRICT while categories are cascaded from the same user delete; whether MySQL/TiDB orders this successfully has **not been verified** (SQLite tests pass). Treat as uncertain.

## Discrepancies
- Missing inverse methods: `User::budgets()`, `Budget::user()`, `Goal::user()`, `Category::budget()`.
- `User::transations()` is a typo alias.
- `sessions.user_id` has no FK.
