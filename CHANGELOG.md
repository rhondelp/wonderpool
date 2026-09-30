# Changelog

All notable changes, NEWEST FIRST (Keep a Changelog style).
Rule: every bullet lists the files touched so the agent never has to diff or search.

<!--
ENTRY TEMPLATE
## [M<id>] <Title> - <YYYY-MM-DD>
### Added / Changed / Fixed / Removed
- <what> (files: path1, path2)
### DB: migrations added/changed (or "none")
### Routes: added/changed (or "none")
### Breaking / Notes
-->

## Unreleased

## [M0] Setup - 2026-10-01
### Added
- Laravel 12 skeleton merged into repo root; Laravel ignore rules merged (files: app/, bootstrap/, config/, database/, public/, routes/, storage/, artisan, composer.json, composer.lock, package.json, package-lock.json, vite.config.js, phpunit.xml, .editorconfig, .gitattributes, .gitignore)
- Env: app name, APP_TIMEZONE=Asia/Manila, MySQL `wonderpool` placeholders (files: .env.example, config/app.php)
- Packages: blade-heroicons, Pest + laravel plugin, Larastan; npm @tailwindcss/forms, @fontsource/poppins, alpinejs, @alpinejs/focus (files: composer.json, composer.lock, package.json, package-lock.json)
- Theme tokens pool-*/garden-*, Poppins 400–700, forms plugin (files: resources/css/app.css)
- Alpine bootstrap with focus plugin (files: resources/js/app.js)
- Layouts + partials: public (mobile menu), admin (sidebar + mobile drawer), shared head/meta, flash (files: resources/views/layouts/public.blade.php, resources/views/layouts/admin.blade.php, resources/views/partials/head.blade.php, resources/views/partials/admin-sidebar.blade.php)
- Components x-ui.button/input/select/textarea/card/badge/modal/alert/flash/empty-state, x-admin.stat-card/page-header (files: resources/views/components/ui/*.blade.php, resources/views/components/admin/*.blade.php)
- Temporary home page and local-only design preview (files: resources/views/home.blade.php, resources/views/design-preview.blade.php, routes/web.php)
- Tooling: Pint psr12, Larastan level 6, GitHub Actions CI (files: pint.json, phpstan.neon, .github/workflows/ci.yml)
- Pest setup + smoke tests (files: tests/Pest.php, tests/TestCase.php, tests/Feature/SmokeTest.php, tests/Unit/.gitkeep)
- README and docs stubs (files: README.md, docs/architecture.md, docs/database.md, docs/booking-flow.md, docs/admin-guide.md, docs/deployment.md)
### Changed
- Skeleton files reformatted by Pint psr12 (files: app/Models/User.php, database/migrations/0001_01_01_00000*_*.php)
### Removed
- Default welcome view and example tests (files: resources/views/welcome.blade.php, tests/Feature/ExampleTest.php, tests/Unit/ExampleTest.php)
### DB: none (Laravel default users/cache/jobs migrations only; not yet run on MySQL)
### Routes: added GET / (home), GET /design-preview (local only)
### Breaking / Notes
- PLAN.md empty at M0 → palette/badge colors provisional (HISTORY D-003, D-004).
- Local MariaDB won't start (damaged InnoDB data); see HISTORY Known Issues.

## [bootstrap] Project tracking system - 2026-10-01
### Added
- Project tracking system and git bootstrap (files: CLAUDE.md, HISTORY.md, CHANGELOG.md, MILESTONES.md, .gitignore)
### DB: none
### Routes: none
