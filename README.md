# ContentVault — Social Media Content Planner & Reminder Dashboard

A self-hosted social media content planner built with **Laravel 13** (API) and **Vue 3** (Composition API, Script Setup) + **Vite + Tailwind CSS v4 + Pinia**.

## Features

- 🔐 **Account Vault** — store platform, handle, profile link, account type, notes & credentials info for all your social accounts.
- 📅 **Daily Planner & Post Queue** — today's queue with one-click **Mark as Posted** / Skip, plus statuses `Draft`, `Scheduled`, `Posted`, `Skipped`.
- ✍️ **Script & Content Studio** — write post scripts/captions, attach media link ideas, tag multiple platforms, and schedule dates.
- 🔔 **Notification System** — daily 08:00 email reminder via the Laravel scheduler + opt-in **browser notifications** for today's queue.
- 📈 **Analytics / History Log** — filter past posted posts by date and platform to track consistency.

## Tech Stack

| Layer     | Tools |
|-----------|-------|
| Backend   | Laravel 13, Eloquent, Sanctum (token auth), MySQL, Artisan scheduler |
| Frontend  | Vue 3 `<script setup>`, Vite 8, Tailwind CSS v4, Pinia, Vue Router 5, Axios |

## Architecture

```
api/v1
├── POST   /login                          # token-based login (Sanctum)
├── POST   /logout
├── GET    /me
├── GET    /posts                          # list + filter (status, date, platform, q)
├── POST   /posts                          # create post + attach platforms
├── GET    /posts/{post}
├── PUT    /posts/{post}                   # update post + sync platforms
├── PATCH  /posts/{post}/status            # Draft|Scheduled|Posted|Skipped
├── DELETE /posts/{post}
├── GET    /posts/today                    # today's scheduled queue w/ counts
├── GET    /posts/history                  # posted-count grouped by date
├── GET    /notifications
├── POST   /notifications/read-all
└── social-accounts CRUD  (GET/POST/PUT/DELETE)
```

### Database schema

- `users` — account owners (Laravel default + Sanctum tokens)
- `social_accounts` — platform, handle, profile_url, account_type, notes, credentials (json)
- `posts` — title, script_body, media_links (json), scheduled_at, status, posted_at
- `post_platform` — pivot linking posts ↔ social accounts with per-platform status & options
- `notifications` — database reminders (morph)

## Local Setup

Requirements: PHP 8.3+, Composer, Node 20+, MySQL (or SQLite), and the `pdo_sqlite` extension enabled for tests.

```bash
# 1. Install dependencies
composer install
npm install

# 2. Configure environment
cp .env.example .env
#  → set DB_*, APP_URL, FRONTEND_URL to your local values
php artisan key:generate

# 3. Create the database and migrate + seed
php artisan migrate --seed
#    Seeder creates the demo user:
#    email:    safwanpaloli7@gmail.com
#    password: Safwanpaloli7@6960

# 4. Build the frontend assets
npm run build
```

### Running (dev)

```bash
# Terminal 1 — Laravel API + SPA
php artisan serve

# Terminal 2 — Vite dev server (hot reload)
npm run dev
```

Open `http://localhost:8000` and sign in with the seeded credentials.

## Scheduled Reminders

The email reminder runs daily at **08:00**:

```bash
php artisan schedule:list        # verify
php artisan app:send-post-reminders   # run it once manually
```

On a server, add one cron entry to run the scheduler every minute:

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

For local testing without cron: `php artisan schedule:work` keeps the scheduler running in the foreground.

Mails are written to `storage/logs/laravel.log` while `MAIL_MAILER=log`. Set a real mailer (SMTP / Mailgun) in `.env` to deliver them.

**Browser notifications** — click the 🔔 *Enable reminders* button in the header once signed in; your browser will then nudge you when today's queue loads.

## Tests

```bash
php artisan test        # 21 feature tests (auth, accounts, posts, reminders)
php vendor/bin/pint     # code style fixer
```
