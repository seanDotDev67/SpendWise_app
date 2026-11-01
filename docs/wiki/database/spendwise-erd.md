# SpendWise Entity Relationship Diagram

The diagram is written in Mermaid and renders on GitHub and in VS Code with Mermaid Markdown preview support. The same source is stored in [spendwise-erd.mmd](./spendwise-erd.mmd).

Data types follow the Laravel schema builder's MySQL mapping (for example `foreignId` becomes `BIGINT UNSIGNED`). Mermaid attribute types cannot contain parentheses, so `varchar_255` means `VARCHAR(255)`. Column details are in the [data dictionary](./data-dictionary.md).

## Notation

| Symbol | Meaning |
|---|---|
| `\|\|` | exactly one (mandatory) |
| `\|o` | zero or one (optional) |
| `o{` | zero or many |
| solid line | enforced by a database foreign key |
| dotted line | logical relationship only, with no foreign key constraint |

## Core domain (enforced by foreign keys)

```mermaid
erDiagram
    users ||--o{ categories : "owns (user_id, CASCADE)"
    users ||--o{ transactions : "owns (user_id, CASCADE)"
    users ||--o{ budgets : "owns (user_id, CASCADE)"
    users ||--o{ goals : "owns (user_id, CASCADE)"
    categories ||--o{ transactions : "classifies (category_id, RESTRICT)"
    categories ||--o| budgets : "limited by (category_id, CASCADE)"

    users {
        bigint_unsigned id PK
        varchar_255 name
        varchar_255 email UK
        timestamp email_verified_at "nullable"
        varchar_255 password
        varchar_100 remember_token "nullable"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
        varchar_255 google_id UK "nullable"
        varchar_255 profile_photo_path "nullable"
        varchar_16 theme "default system"
        tinyint_1 reduced_motion "default 0"
    }

    categories {
        bigint_unsigned id PK
        bigint_unsigned user_id FK
        varchar_255 name "UK(user_id,name,type)"
        enum type "income|expense"
        varchar_255 color "nullable"
        varchar_255 icon "nullable"
        tinyint_1 is_archived "default 0"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
    }

    transactions {
        bigint_unsigned id PK
        bigint_unsigned user_id FK
        bigint_unsigned category_id FK
        decimal_12_2 amount
        enum type "income|expense"
        varchar_255 description "nullable"
        date date
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
        varchar_50 payment_method "nullable"
        text notes "nullable"
    }

    budgets {
        bigint_unsigned id PK
        bigint_unsigned user_id FK "UK(user_id,category_id)"
        bigint_unsigned category_id FK "UK(user_id,category_id)"
        decimal_12_2 amount "monthly limit"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
    }

    goals {
        bigint_unsigned id PK
        bigint_unsigned user_id FK
        varchar_30 name
        varchar_40 icon "default target"
        decimal_12_2 target
        decimal_12_2 saved "default 0"
        date deadline "nullable"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
    }
```

## Infrastructure tables and logical links

```mermaid
erDiagram
    users |o..o{ sessions : "logical link only, no FK"
    users ||..o| password_reset_tokens : "logical link by email, no FK"

    sessions {
        varchar_255 id PK
        bigint_unsigned user_id "nullable, indexed, no FK"
        varchar_45 ip_address "nullable"
        text user_agent "nullable"
        longtext payload
        int last_activity "indexed"
    }

    password_reset_tokens {
        varchar_255 email PK
        varchar_255 token
        timestamp created_at "nullable"
    }

    cache {
        varchar_255 key PK
        mediumtext value
        int expiration "indexed"
    }

    cache_locks {
        varchar_255 key PK
        varchar_255 owner
        int expiration "indexed"
    }

    jobs {
        bigint_unsigned id PK
        varchar_255 queue "indexed"
        longtext payload
        tinyint_unsigned attempts
        int_unsigned reserved_at "nullable"
        int_unsigned available_at
        int_unsigned created_at
    }

    job_batches {
        varchar_255 id PK
        varchar_255 name
        int total_jobs
        int pending_jobs
        int failed_jobs
        longtext failed_job_ids
        mediumtext options "nullable"
        int cancelled_at "nullable"
        int created_at
        int finished_at "nullable"
    }

    failed_jobs {
        bigint_unsigned id PK
        varchar_255 uuid UK
        text connection
        text queue
        longtext payload
        longtext exception
        timestamp failed_at "default CURRENT_TIMESTAMP"
    }
```

`cache`, `cache_locks`, `jobs`, `job_batches` and `failed_jobs` have no relationships to the domain tables. In the first diagram the `users` entity is repeated on purpose, and in the second the `users` link is drawn without repeating its columns.

## Reading the diagram

- **User to everything:** a user is the root owner. Deleting a user cascades to categories, transactions, budgets and goals.
- **Transactions and categories:** every transaction must have a category (mandatory), and a category with transactions cannot be deleted at the database level (`RESTRICT`).
- **Budgets:** the unique key `(user_id, category_id)` limits a category to at most one budget, so from the category side the relationship is `0..1`.
- **Goals:** `goals` is related only to `users`. It is deliberately not linked to `transactions`.
- **No many-to-many relationships and no pivot tables** exist in this schema. SpendWise has no tables for AI conversations, savings contributions or tags.
