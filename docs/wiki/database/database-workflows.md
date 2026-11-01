# Database Workflows

| # | Workflow | Tables read | Tables written |
|---|---|---|---|
| 1 | Registration / login | users, sessions | users, sessions |
| 2 | Income transaction | categories | transactions |
| 3 | Expense transaction | categories | transactions |
| 4 | Category assignment | categories | transactions |
| 5 | Budget create / update | categories, budgets | budgets |
| 6 | Reports | transactions, categories, budgets | none |
| 7 | Savings goals | goals | goals |
| 8 | AI assistant | users, transactions, categories, budgets, goals, sessions | sessions (history), cache (alert) |

## 1. Registration and authentication
Registration (`RegisteredUserController`) validates, inserts into `users` (hashed password, unique email) and logs in. The session ID is regenerated and a `sessions` row is stored with `user_id`. Google login (`GoogleAuthController`) finds the user by `google_id`, or links by verified email, or creates one with a random password and `email_verified_at` set. Password reset uses `password_reset_tokens` (link) or the cache (6-digit code).

## 2–3. Income and expense transactions
`TransactionController::store` validates amount, type, category, date and optional fields. The category is loaded from the user's own categories and its type must equal the transaction type. A row is inserted with `user_id` set to the current user.

## 4. Category assignment
`transactions.category_id` references `categories.id`. The form lists only the user's non-archived categories. Categories with transactions cannot be deleted.

## 5. Budgets
`BudgetController` verifies the category belongs to the user, then `updateOrCreate` on `(user_id, category_id)` writes `budgets.amount`. Update and delete return 403 if the owner differs.

## 6. Reports
`ReportDataService` filters `transactions` by user and month (`SUBSTR(date,1,7)`), groups by category and sums income and expense. Budgets are compared against monthly expense sums. No data is written.

## 7. Savings goals
Create inserts into `goals`. Funding calls `increment('saved', amount)`. No history row is written. Projections are calculated in memory.

## 8. AI assistant
`AssistantFinancialContext` and `SpendingHabitAnalyzer` read the user's rows and `SavingsProjectionService` computes savings figures. The conversation (last messages) is held in the session payload. The guard compares model output with these computed facts before returning the reply.
