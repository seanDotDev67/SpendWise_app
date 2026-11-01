# SpendWise Database Documentation

SpendWise (Laravel 12) uses 12 tables: 5 domain tables (`users`, `categories`, `transactions`, `budgets`, `goals`) and 7 framework tables. There are no AI tables, no contributions table, no pivot tables and no soft deletes.

## Contents
- [spendwise-erd.md](./spendwise-erd.md) – Mermaid ERD (source: [spendwise-erd.mmd](./spendwise-erd.mmd))
- [data-dictionary.md](./data-dictionary.md) – every table and column
- [database-relationships.md](./database-relationships.md)
- [database-architecture.md](./database-architecture.md)
- [database-workflows.md](./database-workflows.md)
- [database-design-evaluation.md](./database-design-evaluation.md)

## Evidence (source files)
- `database/migrations/*` (10 files)
- `app/Models/{User,Category,Transaction,Budget,Goal}.php`
- `app/Http/Controllers/{Transaction,Category,Budget,Goal,Profile,Report,PasswordCode}Controller.php`, `Auth/RegisteredUserController.php`, `Auth/GoogleAuthController.php`
- `app/Services/{ReportDataService,AssistantFinancialContext,SpendingHabitAnalyzer,SavingsProjectionService}.php`
- `app/View/Composers/AiCompanionComposer.php`
- `config/{session,cache,queue,auth}.php`
- `database/seeders/DatabaseSeeder.php`, `database/factories/UserFactory.php`

## Verification and uncertainty
- Column types come from the migration code and Laravel's MySQL grammar, **not** from a live database. TiDB may differ slightly.
- Behaviour of user deletion with CASCADE and RESTRICT on MySQL/TiDB was not tested.
- The `migrations` bookkeeping table is framework-managed and not documented.
- No application code, migrations, config or data were changed.
