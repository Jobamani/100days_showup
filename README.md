# 100days_showup

![Tests](https://github.com/Jobamani/100days_showup/actions/workflows/tests.yml/badge.svg)

A **Community Blog & Q&A platform** built in public as part of **#100DaysOfLaravel**.
One running project, taken from beginner concepts to advanced topics, with a daily build log on LinkedIn.

## Features so far

- **Authentication** with Laravel Breeze (register, login, profile with avatar and bio)
- **Posts**: create, edit, delete, image upload, auto-generated slugs
- **Tags**: many-to-many relationship between posts and tags
- **Comments**: polymorphic comments, so they can attach to more than one model type
- **Roles and permissions**: `user`, `editor` and `admin` (PHP enum), Gates, and a `PostPolicy`
- **Account bans**: banned users are logged out on their next request (custom middleware)
- **Sessions and cookies**: a "Continue reading" link on the dashboard
- **Automated tests**: 75 PHPUnit tests (feature and unit), run on every push with GitHub Actions

## Tech stack

Laravel, PHP 8.2+, MySQL, Blade, Tailwind CSS, Vite, Laravel Sanctum, PHPUnit

## Getting started

```bash
git clone https://github.com/Jobamani/100days_showup.git
cd 100days_showup

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Create a MySQL database named `100days_showup`, then set `DB_DATABASE`, `DB_USERNAME`
and `DB_PASSWORD` in `.env`.

```bash
php artisan migrate
php artisan storage:link

npm run dev          # in one terminal
php artisan serve    # in another
```

Open http://127.0.0.1:8000.

## Running the tests

```bash
php artisan test
```

Tests use an in-memory SQLite database, so your MySQL data is never touched.
They need PHP's `pdo_sqlite` and `gd` extensions.

## Roadmap

- [x] Auth, posts, tags, comments, roles and policies
- [x] Sessions, cookies and automated testing
- [ ] JSON API with Sanctum token authentication
- [ ] AI-assisted post summaries
- [ ] Q&A features, queues and background jobs, caching, deployment

## Follow along

Daily progress is posted on LinkedIn with the hashtag **#100DaysOfLaravel**.

## License

Open source under the [MIT license](https://opensource.org/licenses/MIT).