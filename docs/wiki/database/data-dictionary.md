# SpendWise Data Dictionary

All tables are created by the migrations in `database/migrations/`. Types are the MySQL/MariaDB types produced by the Laravel 12 schema builder. They were derived from the migration code and **not** read from a live database. SQLite maps them to its own storage classes (for example `ENUM` becomes `TEXT` with a `CHECK` constraint).

**Mapping used:** `id()`/`foreignId()` = `BIGINT UNSIGNED`; `string()` = `VARCHAR(255)` unless a length is given; `boolean()` = `TINYINT(1)`; `integer()` = `INT`; `rememberToken()` = `VARCHAR(100) NULL`; `timestamps()` = `created_at`, `updated_at` as `TIMESTAMP NULL`. Dates are Laravel `date()` columns (`DATE`).

Key legend: **PK** primary key, **FK** foreign key, **UQ** unique, **IDX** non-unique index, **-** none.

**Implementation status:** `users`, `categories`, `transactions`, `budgets` and `goals` are the application's domain tables. The remaining tables are Laravel framework infrastructure. No soft deletes (`deleted_at`) exist in any table.

---

## 1. users

**Purpose:** Registered accounts. Stores credentials, optional Google linkage, profile photo location and display preferences. It is the owner (root) of all user data. **PK:** `id`. **FK:** none. **Unique:** `email`, `google_id`.
**Model:** `App\Models\User`. **Relationships:** has many `categories`, `transactions`, `goals`. Budgets are reached through `budgets.user_id` but no relation method exists.

| Column | Data type | Length/Precision | Key | Nullable | Default | Description |
|---|---|---|---|---|---|---|
| id | BIGINT UNSIGNED, auto-increment | 20 digits | PK | No | auto | Unique user identifier. |
| name | VARCHAR | 255 | - | No | none | Display name. Registration stores the combined first and last name here. |
| email | VARCHAR | 255 | UQ | No | none | Login identifier, unique across all users. |
| email_verified_at | TIMESTAMP | - | - | Yes | NULL | When the email was verified. Google sign-in sets it to the current time. |
| password | VARCHAR | 255 | - | No | none | Hashed password (model cast `hashed`). Google-created users get a random hash. |
| remember_token | VARCHAR | 100 | - | Yes | NULL | "Remember me" cookie token. |
| created_at | TIMESTAMP | - | - | Yes | NULL | Row creation time. |
| updated_at | TIMESTAMP | - | - | Yes | NULL | Last update time. |
| google_id | VARCHAR | 255 | UQ | Yes | NULL | Google account identifier for OAuth login (added by `add_google_id_to_users_table`). |
| profile_photo_path | VARCHAR | 255 | - | Yes | NULL | Path to the profile photo on the `public` storage disk (added by `add_profile_preferences_to_users_table`). |
| theme | VARCHAR | 16 | - | No | `'system'` | UI theme preference (same migration). |
| reduced_motion | TINYINT(1) | 1 | - | No | `0` | Accessibility preference to reduce animations. Cast to boolean. |

Not stored: the 16-digit decorative card number shown on the transactions page. It is computed from `id` by `User::cardNumber()`.

---

## 2. categories

**Purpose:** User-defined labels that classify transactions as income or expense. Each user has a private set. **PK:** `id`. **FK:** `user_id` references `users.id` (`ON DELETE CASCADE`). **Unique:** `(user_id, name, type)`.
**Model:** `App\Models\Category`. **Relationships:** belongs to `User`; has many `Transaction`. Budgets reference it through `budgets.category_id`; no relation method exists on the model.

| Column | Data type | Length/Precision | Key | Nullable | Default | Description |
|---|---|---|---|---|---|---|
| id | BIGINT UNSIGNED, auto-increment | 20 digits | PK | No | auto | Category identifier. |
| user_id | BIGINT UNSIGNED | 20 digits | FK, part of UQ | No | none | Owner. Indexed by the foreign key and by the leftmost part of the unique key. |
| name | VARCHAR | 255 | part of UQ | No | none | Category name. Unique per user and type. |
| type | ENUM('income','expense') | - | part of UQ | No | none | Whether the category is for income or expense. |
| color | VARCHAR | 255 | - | Yes | NULL (DB). Model default `'#3b4a3c'` | Hex colour used in charts and lists. Form validation limits it to 7 characters. |
| icon | VARCHAR | 255 | - | Yes | NULL (DB). Model default `'dots'` | Icon identifier. |
| is_archived | TINYINT(1) | 1 | - | No | `0` | Archived categories are hidden from the transaction form. Cast to boolean. |
| created_at | TIMESTAMP | - | - | Yes | NULL | Row creation time. |
| updated_at | TIMESTAMP | - | - | Yes | NULL | Last update time. |

---

## 3. transactions

**Purpose:** The financial ledger. One row per income or expense event. **PK:** `id`. **FK:** `user_id` references `users.id` (`CASCADE`); `category_id` references `categories.id` (`RESTRICT`).
**Model:** `App\Models\Transaction`. **Relationships:** belongs to `User`; belongs to `Category`.

| Column | Data type | Length/Precision | Key | Nullable | Default | Description |
|---|---|---|---|---|---|---|
| id | BIGINT UNSIGNED, auto-increment | 20 digits | PK | No | auto | Transaction identifier. |
| user_id | BIGINT UNSIGNED | 20 digits | FK | No | none | Owner of the transaction. |
| category_id | BIGINT UNSIGNED | 20 digits | FK | No | none | Category that classifies the transaction. Mandatory. |
| amount | DECIMAL | (12,2) | - | No | none | Positive monetary amount. The direction is given by `type`. Form validation requires 0.01 to 9,999,999,999.99. Cast `decimal:2`. |
| type | ENUM('income','expense') | - | - | No | none | Direction of the money. The controller checks that it equals the category's `type`. |
| description | VARCHAR | 255 | - | Yes | NULL | Short free-text label. |
| date | DATE | - | - | No | none | Date the transaction occurred. Cast to a date. |
| created_at | TIMESTAMP | - | - | Yes | NULL | Row creation time. |
| updated_at | TIMESTAMP | - | - | Yes | NULL | Last update time. |
| payment_method | VARCHAR | 50 | - | Yes | NULL | Payment method label (added by `add_payment_details_to_transactions_table`). |
| notes | TEXT | 65,535 bytes | - | Yes | NULL | Longer notes. Form validation limits them to 2000 characters. |

---

## 4. budgets

**Purpose:** A monthly spending limit per user and expense category. **PK:** `id`. **FK:** `user_id` references `users.id` (`CASCADE`); `category_id` references `categories.id` (`CASCADE`). **Unique:** `(user_id, category_id)`.
**Model:** `App\Models\Budget`. **Relationships:** belongs to `Category`. There is no `user()` method on `Budget` and no `budgets()` method on `User`.

| Column | Data type | Length/Precision | Key | Nullable | Default | Description |
|---|---|---|---|---|---|---|
| id | BIGINT UNSIGNED, auto-increment | 20 digits | PK | No | auto | Budget identifier. |
| user_id | BIGINT UNSIGNED | 20 digits | FK, part of UQ | No | none | Owner of the budget. |
| category_id | BIGINT UNSIGNED | 20 digits | FK, part of UQ | No | none | Category the limit applies to. |
| amount | DECIMAL | (12,2) | - | No | none | Monthly limit. The application requires at least 100. |
| created_at | TIMESTAMP | - | - | Yes | NULL | Row creation time. |
| updated_at | TIMESTAMP | - | - | Yes | NULL | Last update time. |

---

## 5. goals

**Purpose:** Savings goals with a target, the amount saved so far and an optional deadline. **PK:** `id`. **FK:** `user_id` references `users.id` (`CASCADE`).
**Model:** `App\Models\Goal`. **Relationships:** none are declared on the model; `User::goals()` is a `hasMany` from the user side.

| Column | Data type | Length/Precision | Key | Nullable | Default | Description |
|---|---|---|---|---|---|---|
| id | BIGINT UNSIGNED, auto-increment | 20 digits | PK | No | auto | Goal identifier. |
| user_id | BIGINT UNSIGNED | 20 digits | FK | No | none | Owner of the goal. |
| name | VARCHAR | 30 | - | No | none | Goal name, for example "New phone". |
| icon | VARCHAR | 40 | - | No | `'target'` | Icon identifier. |
| target | DECIMAL | (12,2) | - | No | none | Amount to reach. The controller requires at least 100. |
| saved | DECIMAL | (12,2) | - | No | `0` | Amount saved so far. Increased by `GoalController::fund`. |
| deadline | DATE | - | - | Yes | NULL | Optional target date. |
| created_at | TIMESTAMP | - | - | Yes | NULL | Row creation time. |
| updated_at | TIMESTAMP | - | - | Yes | NULL | Last update time. |

There is no savings-contribution table: each contribution only increments `goals.saved`, and individual deposits are not recorded.

---

## 6. sessions (framework)

**Purpose:** Database-backed login sessions (`SESSION_DRIVER=database`, the configured default). **PK:** `id`. **FK:** none. `user_id` is indexed but **not** a foreign key.
It also holds the AI assistant's conversation history, which is kept in the session payload rather than in its own table.

| Column | Data type | Length/Precision | Key | Nullable | Default | Description |
|---|---|---|---|---|---|---|
| id | VARCHAR | 255 | PK | No | none | Session identifier. |
| user_id | BIGINT UNSIGNED | 20 digits | IDX (no FK) | Yes | NULL | Authenticated user, if any. |
| ip_address | VARCHAR | 45 | - | Yes | NULL | Client IP (sized for IPv6). |
| user_agent | TEXT | 65,535 bytes | - | Yes | NULL | Browser user-agent string. |
| payload | LONGTEXT | 4 GB | - | No | none | Serialised session data. |
| last_activity | INT | 11 digits | IDX | No | none | Unix timestamp used for expiry. |

## 7. password_reset_tokens (framework)

**Purpose:** Tokens for Laravel's link-based reset broker (`Auth\PasswordResetLinkController`, `Auth\NewPasswordController`). **PK:** `email`. **FK:** none.

| Column | Data type | Length/Precision | Key | Nullable | Default | Description |
|---|---|---|---|---|---|---|
| email | VARCHAR | 255 | PK | No | none | Email requesting the reset. |
| token | VARCHAR | 255 | - | No | none | Hashed reset token. |
| created_at | TIMESTAMP | - | - | Yes | NULL | Issue time. Tokens expire after 60 minutes (`config/auth.php`). |

The separate 6-digit code reset (`PasswordCodeController`) does **not** use this table. Its hashed codes and attempt counters are stored in the cache.

## 8. cache (framework)

**Purpose:** Database cache store (`CACHE_STORE=database`). Used for password-reset codes, attempt counters and the AI companion alert. **PK:** `key`.

| Column | Data type | Length/Precision | Key | Nullable | Default | Description |
|---|---|---|---|---|---|---|
| key | VARCHAR | 255 | PK | No | none | Cache key. |
| value | MEDIUMTEXT | 16 MB | - | No | none | Serialised value. |
| expiration | INT | 11 digits | IDX | No | none | Unix expiry time. |

## 9. cache_locks (framework)

**Purpose:** Atomic locks for the cache store. **PK:** `key`.

| Column | Data type | Length/Precision | Key | Nullable | Default | Description |
|---|---|---|---|---|---|---|
| key | VARCHAR | 255 | PK | No | none | Lock name. |
| owner | VARCHAR | 255 | - | No | none | Lock owner token. |
| expiration | INT | 11 digits | IDX | No | none | Unix expiry time. |

## 10. jobs (framework)

**Purpose:** Queue storage (`QUEUE_CONNECTION=database`). The application code inspected does not dispatch queued jobs (no `ShouldQueue` or `dispatch()` usages), so the table is created but likely unused. **PK:** `id`.

| Column | Data type | Length/Precision | Key | Nullable | Default | Description |
|---|---|---|---|---|---|---|
| id | BIGINT UNSIGNED, auto-increment | 20 digits | PK | No | auto | Job identifier. |
| queue | VARCHAR | 255 | IDX | No | none | Queue name. |
| payload | LONGTEXT | 4 GB | - | No | none | Serialised job. |
| attempts | TINYINT UNSIGNED | 3 digits | - | No | none | Attempts so far. |
| reserved_at | INT UNSIGNED | 10 digits | - | Yes | NULL | When a worker reserved the job. |
| available_at | INT UNSIGNED | 10 digits | - | No | none | When the job becomes available. |
| created_at | INT UNSIGNED | 10 digits | - | No | none | Creation time as a Unix timestamp. |

## 11. job_batches (framework)

**Purpose:** Metadata for batched jobs. **PK:** `id`.

| Column | Data type | Length/Precision | Key | Nullable | Default | Description |
|---|---|---|---|---|---|---|
| id | VARCHAR | 255 | PK | No | none | Batch identifier. |
| name | VARCHAR | 255 | - | No | none | Batch name. |
| total_jobs | INT | 11 digits | - | No | none | Jobs in the batch. |
| pending_jobs | INT | 11 digits | - | No | none | Jobs not yet processed. |
| failed_jobs | INT | 11 digits | - | No | none | Number of failures. |
| failed_job_ids | LONGTEXT | 4 GB | - | No | none | IDs of failed jobs. |
| options | MEDIUMTEXT | 16 MB | - | Yes | NULL | Serialised batch options. |
| cancelled_at | INT | 11 digits | - | Yes | NULL | Cancellation time. |
| created_at | INT | 11 digits | - | No | none | Creation time. |
| finished_at | INT | 11 digits | - | Yes | NULL | Completion time. |

## 12. failed_jobs (framework)

**Purpose:** Records of queued jobs that failed. **PK:** `id`. **Unique:** `uuid`.

| Column | Data type | Length/Precision | Key | Nullable | Default | Description |
|---|---|---|---|---|---|---|
| id | BIGINT UNSIGNED, auto-increment | 20 digits | PK | No | auto | Failure record identifier. |
| uuid | VARCHAR | 255 | UQ | No | none | Unique job UUID. |
| connection | TEXT | 65,535 bytes | - | No | none | Queue connection name. |
| queue | TEXT | 65,535 bytes | - | No | none | Queue name. |
| payload | LONGTEXT | 4 GB | - | No | none | Serialised job. |
| exception | LONGTEXT | 4 GB | - | No | none | Exception and stack trace. |
| failed_at | TIMESTAMP | - | - | No | `CURRENT_TIMESTAMP` | Time of failure. |

The `migrations` table that Laravel maintains itself (`id`, `migration`, `batch`) is created by the framework's migrator rather than by a project migration, so it is not documented here.
