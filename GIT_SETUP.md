# eMunicipal — Git Repository Setup Instructions

Run these commands **in order** from the project root (`C:\xampp\htdocs\eMunicipal`).

> **Before you start:**
> - Confirm all SQL dump files have been removed from `storage/` (done in Phase 2).
> - Confirm `.env` is listed in `.gitignore` (it is).
> - Have your GitHub username and repository name ready.

---

## Step 1 — Initialise the local repository

```bash
cd C:\xampp\htdocs\eMunicipal
git init
git branch -m main
```

---

## Step 2 — Stage all source files

```bash
git add .
```

This will stage everything not excluded by `.gitignore`.  
The following are automatically excluded and will NOT be staged:

| Excluded | Why |
|---|---|
| `.env` | Contains real SMTP and DB credentials |
| `vendor/` | Restored via `composer install` |
| `storage/logs/*.log` | Runtime logs, machine-specific |
| `storage/uploads/**` | User-uploaded documents, not source code |
| `storage/*.sql` | Database dumps (removed in Phase 2) |
| `*.log` | Any stray log files |
| `*.swp`, `*.swo` | Editor temp files |
| `.idea/`, `.vscode/` | IDE config folders |

The following **will** be staged (they are required):

| Tracked | Why |
|---|---|
| `app/**/*.php` | Application source code |
| `views/**/*.php` | View templates |
| `config/*.php` | Configuration files (values from .env, no secrets) |
| `routes/web.php` | Route definitions |
| `database/schema.sql` | Database schema (required for deployment) |
| `database/seeds.sql` | Seed data (required for deployment) |
| `database/schema.sql`, `database/seeds.sql` | Schema and seed data (required for deployment) |
| `public/assets/css/`, `public/assets/js/`, `public/assets/images/` | Frontend assets |
| `public/.htaccess`, `.htaccess` | Apache routing and security rules |
| `composer.json`, `composer.lock` | Dependency definitions |
| `.env.example` | Configuration template (no real values) |
| `.gitignore` | Ignore rules |
| `DEPLOYMENT.md` | Deployment guide |
| `tests/` | Test suite |

---

## Step 3 — Review what will be committed

```bash
git status
```

Verify:
- `.env` does NOT appear in the staged list.
- `storage/*.sql` does NOT appear.
- `vendor/` does NOT appear.
- `database/schema.sql`, `database/seeds.sql` DO appear (these are correct).

If `.env` appears, stop immediately and run:
```bash
git rm --cached .env
```

---

## Step 4 — Create the initial commit

```bash
git commit -m "Initial commit — eMunicipal Biringan City academic demonstration project"
```

---

## Step 5 — Create a private GitHub repository

1. Go to https://github.com/new
2. Set the repository to **Private**.
3. Do NOT initialise with a README (you already have one).
4. Copy the repository URL (e.g. `https://github.com/youraccount/emunicipal.git`).

---

## Step 6 — Connect and push (requires your approval first)

```bash
git remote add origin https://github.com/youraccount/emunicipal.git
git push -u origin main
```

> **Important:** Only run this step after you have confirmed in Step 3 that
> no credentials are staged. Do not push until all blockers are resolved.

---

## Step 7 — Verify on GitHub

After pushing:
1. Open the repository on GitHub.
2. Confirm `.env` does not exist in the file browser.
3. Confirm `storage/` contains only `.gitkeep` files.
4. Confirm `vendor/` is not present.
5. Confirm `database/schema.sql` is present (required for deployment).

---

## Note on `vendor/`

`vendor/` is excluded from version control because it can always be restored
from the committed `composer.lock` file:

```bash
# On the production server after cloning:
composer install --no-dev --optimize-autoloader
```

Never force-add `vendor/` to the repository.

---

## If credentials were already exposed

If `.env` or any file containing real passwords was accidentally committed to
Git history at any point, **deleting the file in a new commit does not remove
it from the history**. Old commits still contain the values. In that case:

1. Immediately revoke and rotate every exposed credential.
2. Use `git filter-repo` or BFG Repo Cleaner to rewrite history.
3. Force-push the cleaned history.
4. Notify any collaborators who may have cloned the repository.

Since this is the **first commit** (no history yet), this risk does not apply
here — as long as `.env` is confirmed excluded before `git commit`.
