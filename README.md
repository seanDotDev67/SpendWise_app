import pypandoc

markdown = r"""# SpendWise

> **Spend Smart. Live Wise.**  
> A web-based personal finance tracker that helps users record income and expenses, organize transactions into categories, and monitor monthly budgets.

SpendWise is a college team project built with Laravel. This README is the team's shared guide for setting up the project, understanding the backend/frontend responsibilities, following the Git workflow, and coordinating implementation without getting lost.

> **Important:** This README describes the intended project structure and the current status reported by the team. Check the actual repository, migrations, and routes before assuming a feature is complete. Update this document whenever the team makes a significant change.

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Problem and Solution](#problem-and-solution)
3. [Target Users](#target-users)
4. [Features and Development Status](#features-and-development-status)
5. [Technology Stack](#technology-stack)
6. [Team Responsibilities](#team-responsibilities)
7. [How the Application Fits Together](#how-the-application-fits-together)
8. [Project Structure](#project-structure)
9. [Requirements](#requirements)
10. [Set Up the Project Locally](#set-up-the-project-locally)
11. [Environment Configuration](#environment-configuration)
12. [Database Overview](#database-overview)
13. [Backend and Frontend Contract](#backend-and-frontend-contract)
14. [Routes and How to Inspect Them](#routes-and-how-to-inspect-them)
15. [Git and GitHub Team Workflow](#git-and-github-team-workflow)
16. [Avoiding and Resolving Merge Conflicts](#avoiding-and-resolving-merge-conflicts)
17. [Security Rules](#security-rules)
18. [Testing Before a Pull Request](#testing-before-a-pull-request)
19. [Useful Commands](#useful-commands)
20. [Feature Roadmap](#feature-roadmap)
21. [Troubleshooting](#troubleshooting)
22. [Team Information](#team-information)

---

## Project Overview

SpendWise provides one place for users to manage their personal financial records. Users can record income and expenses, classify transactions, create monthly category budgets, and review summaries that help them understand their spending.

The long-term goal is to provide a simple financial overview with useful reports and an AI assistant that explains spending patterns and offers practical suggestions based on recorded data.

## Problem and Solution

### Problems

- People may forget small daily expenses, such as food, transportation, and school supplies.
- Users may not know which categories consume most of their money.
- Paper notes or scattered records can be difficult to update and compare with a monthly budget.
- Without a clear summary, users may not notice when spending approaches or exceeds a planned limit.

### Proposed solution

SpendWise centralizes income and expense records, organizes them by category, supports monthly budgets, and displays financial summaries. Its planned AI assistant can interpret recorded totals and suggest practical next steps. SpendWise helps users make more informed decisions; it does not guarantee savings or replace professional financial advice.

## Target Users

| User group | How SpendWise helps |
|---|---|
| Students | Track allowances and daily expenses such as food, transport, and school supplies. |
| Employees | Record income and monitor bills, groceries, transport, and other personal expenses. |
| Individuals | Organize personal financial records, set budgets, and review spending habits. |

## Features and Development Status

Status must reflect the actual code in the shared repository. Update this table as features are merged.

| Feature | Reported status | Notes |
|---|---|---|
| Registration and login | Implemented | Laravel Breeze / Blade authentication is reported as set up. Verify the actual branch before relying on this. |
| Category management backend | Implemented | CRUD, validation, and per-user ownership checks are reported as completed. |
| Category management UI | In progress | Coordinate Blade view names and variables with the backend owner. |
| Transaction management | Planned | Income/expense creation, editing, listing, filtering, and deletion. |
| Monthly budgets | Planned | Category spending limits and progress calculations. |
| Dashboard summaries | Planned / in progress | Balance, monthly income, monthly expenses, recent transactions, and budget progress. |
| Reports | Planned | Category breakdowns and spending trends. |
| AI financial assistant | Planned | Analysis and recommendations based on the user's recorded data. |

Do not mark a feature as complete just because a page exists. Confirm that validation, authorization, database behavior, and tests work.

## Technology Stack

| Layer | Technology |
|---|---|
| Backend framework | Laravel — check `composer.json` for the exact version |
| Language | PHP — check the project's required version |
| Database | MySQL or MariaDB |
| Authentication | Laravel Breeze / Blade, if present in the repository |
| Views | Laravel Blade |
| Frontend assets | Tailwind CSS and Vite, if configured |
| JavaScript dependencies | Node.js and npm |
| Version control | Git and GitHub |

Use the versions already defined in the repository's `composer.json`, `composer.lock`, `package.json`, and `package-lock.json`. Do not upgrade framework or dependency versions without team agreement.

---

## Team Responsibilities

Current group: **Sheila, Sean, Frank, and Kane**.

| Member | Main role | Responsibilities |
|---|---|---|
| Frank | Project Manager / Requirements | Coordinate the team, organize tasks, clarify requirements, and keep the project direction consistent. |
| Kane | Research & Documentation | Related literature, references, research materials, and supporting documentation. |
| Sean | Backend & Database Developer | Database design, ERD, data dictionary, migrations, Eloquent models, controllers, validation, backend logic, and integration support. |
| Sheila | UI/UX & Frontend | Interface design, page layouts, Blade views, frontend components, and visual consistency. |

### Team coordination rules

- Agree on a feature's expected behavior before coding.
- Backend and frontend members should agree on route names, form fields, view names, and variables before integration.
- Frontend members should not query the database directly from Blade views.
- Backend members should not unexpectedly rename or restructure existing views.
- If a task needs changes in another person's files, discuss it first and coordinate the branch/PR.
- Record decisions in the team chat or project documentation so teammates who were not present can catch up.

---

## How the Application Fits Together

A typical request follows this path:

1. A user visits a URL or submits a form.
2. Laravel matches the request to a route in `routes/web.php`.
3. Authentication middleware checks whether the user is logged in, when required.
4. The controller validates input and checks permissions.
5. The controller uses an Eloquent model to read or change database records.
6. The controller returns a Blade view, redirect, or JSON response.
7. The view displays the data and validation feedback to the user.

**Responsibilities by layer:**

- **Route:** maps an HTTP method and URL to an action.
- **Controller:** handles the request and coordinates the response.
- **Model:** represents data and relationships with database tables.
- **Migration:** describes database schema changes.
- **Blade view:** renders the interface; it should not contain database access or core business logic.
- **Service class (when needed):** holds reusable business logic, such as financial summary calculations.

---

## Project Structure

Your exact structure may differ. This is a guide to common Laravel locations:

```text
spendwise/
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Request handling and responses
│   │   ├── Middleware/        # Request filters, if custom middleware exists
│   │   └── Requests/          # Form Request validation, if used
│   ├── Models/                # Eloquent models (User, Category, etc.)
│   └── Services/              # Reusable business logic, if introduced
├── database/
│   ├── factories/             # Test/fake model data
│   ├── migrations/            # Database schema history
│   └── seeders/               # Development/sample data
├── resources/
│   ├── views/                 # Blade templates
│   ├── css/                   # Frontend styles
│   └── js/                    # Frontend JavaScript
├── routes/
│   └── web.php                # Web routes
├── tests/
│   ├── Feature/               # Request/feature tests
│   └── Unit/                  # Small isolated tests
├── config/                    # Framework configuration
├── public/                    # Public entry point and built assets
├── .env                       # Local secrets/settings; never commit
├── .env.example               # Shared environment template
├── composer.json              # PHP dependencies and scripts
├── package.json               # Frontend dependencies and scripts
└── README.md
```

---

## Requirements

Install these tools before setup:

- **Git** — to clone the repository and work with branches.
- **PHP** — use the version required by the repository.
- **Composer** — installs PHP/Laravel dependencies.
- **MySQL or MariaDB** — XAMPP is one local option.
- **Node.js and npm** — needed if the project uses Vite/frontend dependencies.
- **A code editor** — for example, VS Code or PhpStorm.

On Windows, XAMPP can provide PHP and MySQL. Make sure the PHP executable and Composer are available in your terminal's PATH. Check the actual version requirements in the repository before installing or upgrading tools.

---

## Set Up the Project Locally

Follow these steps for a **fresh local setup**. Do not run destructive migration commands against a database containing data you need to keep.

### 1. Clone the repository

Replace the placeholder URL with the team's actual GitHub repository URL.

```bash
git clone https://github.com/OWNER/REPOSITORY.git
cd REPOSITORY
```

If you already cloned the project, do not clone it again. Enter the existing project directory instead.

### 2. Check the project and tool versions

```bash
git status
php -v
composer --version
node -v
npm -v
```

If a command is not found, install the missing tool or fix your PATH before continuing.

### 3. Install PHP dependencies

```bash
composer install
```

Use `composer install`, not `composer update`, for normal setup. `install` uses the versions locked by the project.

### 4. Create your local environment file

Windows Command Prompt:

```bat
copy .env.example .env
```

PowerShell also supports:

```powershell
Copy-Item .env.example .env
```

Git Bash, macOS, or Linux:

```bash
cp .env.example .env
```

If `.env` already exists, do not overwrite it without checking its contents.

### 5. Generate the application key

```bash
php artisan key:generate
```

This writes a local `APP_KEY` to your `.env`. Never copy another teammate's private `.env` into GitHub.

### 6. Create a local database

Create a database using phpMyAdmin, MySQL Workbench, or the MySQL CLI. Example database name:

```sql
CREATE DATABASE spendwise
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

If your team has agreed on another database name, use that name consistently.

### 7. Configure the database in `.env`

Open `.env` and set the connection details for your local machine. Example:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spendwise
DB_USERNAME=root
DB_PASSWORD=
```

These values are examples only. Your username, password, and port may differ. XAMPP installations sometimes use a different MySQL port.

### 8. Run migrations

```bash
php artisan migrate
```

Migrations create tables defined by the project. If this fails, read the full error and check the database name, port, credentials, and migration files before trying destructive fixes.

If the project provides seeders and the team approves using them:

```bash
php artisan db:seed
```

Do not run seeders blindly if they could create duplicate or unwanted sample data.

### 9. Install and build frontend assets

If the repository contains `package.json`:

```bash
npm install
npm run build
```

During active frontend development, use a second terminal for:

```bash
npm run dev
```

### 10. Start Laravel

```bash
php artisan serve
```

Open the local URL shown in the terminal, usually `http://127.0.0.1:8000`.

### 11. Verify your setup

In another terminal, run:

```bash
php artisan route:list
php artisan migrate:status
git status
```

Try registering a development account and logging in. Confirm that the relevant pages load and that the database tables exist.

---

## Environment Configuration

- `.env` contains local machine settings and secrets. It must not be committed.
- `.env.example` documents the settings a teammate needs. It should not contain real passwords, API keys, or production secrets.
- If a new environment variable is required, add a safe placeholder or explanation to `.env.example` and tell the team.
- After changing configuration, Laravel may need its configuration cache cleared in local development:

```bash
php artisan config:clear
```

Never post database passwords, `APP_KEY`, AI provider API keys, or other secrets in a GitHub issue, pull request, screenshot, or team chat.

---

## Database Overview

The database design is being developed around these core entities. Check `database/migrations/` to see which tables are actually present in the current branch.

### `users`

Laravel authentication accounts. Passwords must be stored as hashes, never plaintext.

### `categories`

Suggested fields:

| Column | Purpose |
|---|---|
| `id` | Primary key |
| `user_id` | Owner; foreign key to `users.id` |
| `name` | Category name, such as Food or Salary |
| `type` | `income` or `expense` |
| `color` | Optional UI color |
| `icon` | Optional icon identifier |
| `is_archived` | Whether the category is hidden from new transaction forms, if implemented |
| timestamps | Created/updated times |

A category belongs to one user. A user may have multiple categories. A uniqueness rule such as `(user_id, name, type)` can prevent duplicate names for the same user and type.

### `transactions` — planned

Suggested fields include `id`, `user_id`, `category_id`, `type`, `amount`, `description`, `transaction_date`, timestamps, and optional `deleted_at` for soft deletes.

- Store money using a decimal database type, such as `DECIMAL(12,2)`.
- Store amounts as positive values and use `type` to distinguish income from expenses.
- Validate that the selected category belongs to the current user and that its type matches the transaction type.
- Include only non-deleted transactions in financial totals if soft deletes are enabled.

### `budgets` — planned

Suggested fields include `id`, `user_id`, `category_id`, `amount`, `period_month`, and timestamps.

- `period_month` should represent the first day of the month, for example `2026-10-01`.
- A unique constraint on `(user_id, category_id, period_month)` can prevent duplicate budgets.
- Budget usage should be calculated from expense transactions; do not manually store a spending total that can become stale.

### AI tables — planned

Possible tables are `ai_conversations`, `ai_messages`, and `ai_insights`. Add these after core transaction and budget calculations are reliable. Store only the minimum financial context required for an insight and scope all records to the owning user.

### Relationship summary

```text
User 1 ─── many Categories
User 1 ─── many Transactions
Category 1 ─── many Transactions
User 1 ─── many Budgets
Category 1 ─── many Budgets
User 1 ─── many AI Conversations
AI Conversation 1 ─── many AI Messages
User 1 ─── many AI Insights
```

Database foreign keys and Laravel authorization checks should work together. Never rely on a hidden form field or user-supplied `user_id` to determine ownership.

---

## Backend and Frontend Contract

The backend and frontend must agree on how a page receives data and submits changes. The names below are examples and should be checked against the current controller and route implementation.

| Page | Example data supplied by backend | Expected frontend behavior |
|---|---|---|
| `categories.index` | `$categories` | Display the signed-in user's categories. |
| `categories.create` | No existing category required | Submit a form to the category store route. |
| `categories.edit` | `$category` | Pre-fill the selected category's editable fields. |
| `transactions.index` | `$transactions`, filter values | Display records and preserve selected filters. |
| `transactions.create` | `$categories` eligible for the chosen type | Submit transaction details to the backend. |
| `budgets.index` | `$budgets` with calculated spending data | Display limits, amounts spent, and progress. |
| `dashboard` | Summary totals, recent transactions, category totals, budget progress | Render dashboard cards and charts. |
| `reports.index` | Filtered summary/chart data | Display reports for the selected period. |

### Integration rules

1. Backend developers should document the route name, HTTP method, accepted form fields, validation rules, and response behavior.
2. Frontend developers should use the agreed route names and field names rather than guessing them.
3. Blade views should render supplied data; they should not contain direct database queries.
4. Validation errors should be shown near the relevant fields where practical.
5. If a required variable is missing, coordinate with the backend owner rather than adding database logic to the view.
6. Keep a short integration note in the pull request when changing a route, field name, or view data contract.

---

## Routes and How to Inspect Them

Routes change as the application grows. The source of truth is `routes/web.php` and the live Laravel route list.

Run:

```bash
php artisan route:list
```

To see middleware and more route details, if supported by the installed Laravel version:

```bash
php artisan route:list -v
```

Typical resource route patterns may look like these, but do not assume they are registered until you inspect the project:

| Method | URI pattern | Purpose |
|---|---|---|
| GET | `/categories` | List categories |
| GET | `/categories/create` | Show category form |
| POST | `/categories` | Create category |
| GET | `/categories/{category}/edit` | Show edit form |
| PUT/PATCH | `/categories/{category}` | Update category |
| DELETE | `/categories/{category}` | Delete category |

Routes for authenticated features should use appropriate authentication middleware. A route being hidden from the navigation is not a security measure.

---

## Git and GitHub Team Workflow

### Golden rules

- **Never push feature work directly to `main`.**
- Every feature or fix should use its own branch.
- Open a pull request (PR) for review and integration.
- Pull the latest `main` before beginning a task.
- Do not work on the same files as another teammate without coordinating.
- Commit small, logical changes with clear messages.
- Never commit `.env`, credentials, generated secrets, or unrelated files.

### 1. Clone the repository (first time only)

```bash
git clone https://github.com/OWNER/REPOSITORY.git
cd REPOSITORY
```

### 2. Start a new task from the latest `main`

```bash
git switch main
git pull origin main
git switch -c feature/category-views
```

Replace `feature/category-views` with a branch name that describes your task. If your Git version does not support `git switch`, use `git checkout` equivalents.

### 3. Work and inspect changes

```bash
git status
git diff
```

Make sure the changes are yours and that no secrets or unrelated files are included.

### 4. Stage and commit only the files for your task

Prefer adding specific paths:

```bash
git add resources/views/categories
git commit -m "Build category management views"
```

Use paths that match your actual changes. If you modify more than one area, stage each intended path. Avoid `git add .` when you have unrelated changes in the working tree.

### 5. Push the feature branch

```bash
git push -u origin feature/category-views
```

The `-u` sets the upstream branch so future pushes can usually use `git push`.

### 6. Open a Pull Request on GitHub

1. Open the team's repository on GitHub.
2. Select **Pull requests** → **New pull request**.
3. Set the base branch to `main` and compare branch to your feature branch.
4. Describe what changed, how it was tested, and any integration notes.
5. Link the task or issue if the team uses them.
6. Request a review from the relevant teammate.
7. Resolve feedback, push updates to the same feature branch, and merge only after review and required checks.

### 7. Update your local `main` after the PR is merged

```bash
git switch main
git pull origin main
```

For your next task, create a new branch from the updated `main`.

### Branch naming examples

- `feature/category-views`
- `feature/transaction-crud`
- `feature/monthly-budgets`
- `feature/dashboard-summary`
- `fix/category-validation`
- `docs/update-readme`

### Commit message examples

- `Add category ownership validation`
- `Build category creation form`
- `Add monthly budget migration`
- `Fix transaction date filtering`
- `Document local setup steps`

---

## Avoiding and Resolving Merge Conflicts

A merge conflict means Git cannot automatically combine changes, often because two branches changed the same part of a file. Conflicts are normal; the goal is to prevent avoidable ones and resolve them carefully.

### Prevent conflicts

1. Pull the latest `main` before creating a branch.
2. Keep branches focused on one feature.
3. Coordinate before editing shared files such as `routes/web.php`, shared layouts, or migrations.
4. Tell teammates when a shared file must change.
5. Open PRs early and keep them reasonably small.
6. Do not copy another teammate's code into your branch without coordinating.
7. Avoid changing formatting across entire files when only a small change is needed.

### Keep your feature branch updated

Commit or stash your work first, then:

```bash
git fetch origin
git switch feature/category-views
git merge origin/main
```

Resolve any conflicts, run tests, then commit the merge and push:

```bash
git status
# Edit conflicted files and remove the conflict markers.
git add path/to/resolved-file
git commit
git push
```

The example branch name should be replaced with your own branch.

### What conflict markers mean

```text
<<<<<<< HEAD
Your branch's version
=======
The incoming version from main
>>>>>>> origin/main
```

Open the file, understand both versions, and combine the intended changes. Remove all conflict markers before staging. Do not automatically accept one side without reviewing what it removes.

If you are unsure, stop and ask the teammate who owns the affected code. Never resolve a conflict by deleting someone else's work just to make Git proceed.

> **Important:** avoid `git reset --hard`, `git clean -fd`, or destructive migration commands unless you understand exactly what will be deleted. If you are unsure, ask for help before running them.

---

## File Ownership

Ownership reduces accidental overlap; it does not mean teammates can never help in another area.

| Area | Primary owner | Coordination notes |
|---|---|---|
| `app/Models/` | Backend | Discuss model fields and relationships before changing them. |
| `app/Http/Controllers/` | Backend | Agree on expected data for each view. |
| `database/migrations/` | Backend | Coordinate schema changes; migrations are shared history. |
| `routes/web.php` | Backend, with team coordination | Frontend members should request route changes instead of editing routes independently. |
| `resources/views/` | Frontend/UI | Backend should communicate variable names and validation behavior before changing view contracts. |
| `resources/css/`, `resources/js/` | Frontend/UI | Coordinate shared layouts and build configuration. |
| `README.md` and project documentation | Team / documentation owner | Tell the team when setup or workflow instructions change. |

If a feature requires coordinated changes in backend and frontend, agree on the contract first and make the PR description explicit about both sides.

---

## Security Rules

- **Passwords:** use Laravel authentication and hashing. Never store plaintext passwords.
- **Secrets:** never commit `.env`, API keys, database passwords, or private tokens.
- **Ownership:** scope every financial query to the authenticated user.
- **Authorization:** validate ownership on reads, updates, and deletes—not only when listing records.
- **Mass assignment:** configure model `$fillable` or `$guarded` carefully and never blindly accept ownership fields from user input.
- **Validation:** validate amounts, dates, category types, and category ownership on the server.
- **Database integrity:** use foreign keys and unique constraints for critical relationships.
- **CSRF:** keep Laravel CSRF protection enabled for web forms and include `@csrf` in state-changing Blade forms.
- **HTTP methods:** use POST/PUT/PATCH/DELETE appropriately; do not perform destructive actions through GET requests.
- **AI features:** keep AI provider keys server-side, provide only necessary user-scoped financial context, and never allow AI output alone to execute financial changes.
- **Errors:** do not expose stack traces, credentials, or sensitive database details to normal users in production.

A hidden button, disabled UI control, or hidden `user_id` field is not authorization. The server must enforce permissions.

---

## Testing Before a Pull Request

Before opening a PR, review the following:

- [ ] `git status` shows only intended changes.
- [ ] No `.env`, credentials, or generated secrets are staged.
- [ ] Migrations run successfully in the intended development environment.
- [ ] Relevant routes appear in `php artisan route:list`.
- [ ] Validation handles missing, invalid, and unexpected input.
- [ ] Users cannot view or modify another user's records by changing an ID.
- [ ] Empty states and validation errors are handled.
- [ ] Related frontend pages still receive the expected variables.
- [ ] Relevant tests pass.
- [ ] The PR description explains the change and how it was tested.

Useful checks:

```bash
php artisan route:list
php artisan migrate:status
php artisan test
git diff --check
git status
```

If a test has not been run, say so in the PR rather than claiming it passed.

---

## Useful Commands

### Laravel

```bash
php artisan serve              # Start local development server
php artisan route:list         # Inspect registered routes
php artisan migrate            # Run pending migrations
php artisan migrate:status     # Inspect migration status
php artisan make:model Category -m
php artisan make:controller CategoryController --resource
php artisan test               # Run automated tests
php artisan config:clear       # Clear cached configuration
php artisan tinker             # Explore models/queries interactively
```

**Destructive command warning:**

```bash
php artisan migrate:fresh
```

This drops all tables and recreates them. Use it only in a disposable local development database when you are certain its data can be deleted. Never run it on a shared or production database without an explicitly approved recovery plan.

### Git

```bash
git status                     # Check working tree and branch
git branch                     # List local branches
git fetch origin               # Fetch remote updates
git pull origin main           # Update local main while on main
git diff                       # Review unstaged changes
git diff --staged              # Review staged changes
git log --oneline -10          # Inspect recent commits
git switch main                # Switch to main
git switch -c feature/name     # Create and switch to a branch
git push -u origin feature/name
```

### Frontend assets

```bash
npm install                    # Install dependencies from package files
npm run dev                    # Run Vite development server
npm run build                  # Build production assets
```

---

## Feature Roadmap

Update this checklist as the team completes and merges work.

- [ ] Confirm local setup works for all teammates
- [ ] Verify authentication and registration
- [ ] Complete category management integration (backend + views)
- [ ] Implement transaction migrations, models, and CRUD
- [ ] Implement transaction filtering
- [ ] Implement monthly budgets and progress calculations
- [ ] Implement dashboard summaries
- [ ] Implement reports and spending trends
- [ ] Add tests for ownership, validation, and financial calculations
- [ ] Add AI-generated spending insights
- [ ] Add AI follow-up conversations if time permits
- [ ] Review documentation and prepare the final demonstration

---

## Troubleshooting

### `composer` or `php` is not recognized

Install PHP/Composer or configure the Windows PATH. Restart the terminal after changing PATH, then retry `php -v` and `composer --version`.

### `npm` is not recognized

Install Node.js, which includes npm, and restart your terminal.

### Database connection error

- Confirm MySQL/MariaDB is running.
- Check `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.
- Confirm the database exists.
- Run `php artisan config:clear` after local configuration changes.
- Read the complete error message before changing settings.

### `No application encryption key has been specified`

Run:

```bash
php artisan key:generate
```

### A route does not appear

Run:

```bash
php artisan route:list
```

Then inspect `routes/web.php` and the relevant controller. Do not assume a route exists because a link appears in a view.

### A migration fails

Read the first relevant error. Check table creation order, foreign-key types, duplicate constraints, database connection, and whether the migration has already run. Coordinate schema changes with the backend owner before editing a migration that teammates may already have run.

### Git says there are merge conflicts

Run `git status`, open the listed files, carefully combine both intended changes, remove conflict markers, run tests, then commit and push. Ask for help if the correct combination is unclear.

### A teammate's view has an undefined variable

Compare the Blade variable with the data passed by the controller. Agree on one variable name and update the backend or view intentionally; do not add database queries to Blade as a workaround.

---

## Team Information

| Member | Role |
|---|---|
| Frank | Project Manager / Requirements |
| Kane | Research & Documentation |
| Sean | Backend & Database Developer |
| Sheila | UI/UX & Frontend |

**Repository:** Replace with the team's GitHub URL.  
**Project board / task tracker:** Add the team link if one is being used.  
**Team communication channel:** Add the agreed channel or group details if useful.

---

## Final Reminder

When you start work, first read this README, check the current branch, and sync with `main`. Work on a focused feature branch, test your changes, and open a pull request. If you're unsure about a route, schema change, view variable, or conflict, ask before guessing.

Built by the SpendWise team.  
**Spend Smart. Live Wise.**
"""

output_path = "/mnt/data/README.md"
pypandoc.convert_text(markdown, "md", format="md", outputfile=output_path, extra_args=["--standalone"])
print(f"Created {output_path}")
