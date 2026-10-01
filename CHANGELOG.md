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

## [M2] Admin foundation - 2026-10-01
### Added
- Admin sign-in/out at /admin/login: 5 failed attempts per email+IP lockout, disabled accounts rejected, session regeneration, last_login_at, login/logout audit (files: app/Http/Controllers/Admin/Auth/LoginController.php, app/Http/Requests/Admin/LoginRequest.php, resources/views/layouts/auth.blade.php, resources/views/admin/auth/login.blade.php)
- Forced/voluntary own password change; owner seeder flags the owner to change the .env password on first login (files: app/Http/Controllers/Admin/Auth/PasswordController.php, app/Http/Requests/Admin/UpdateOwnPasswordRequest.php, resources/views/admin/auth/change-password.blade.php, database/seeders/OwnerSeeder.php)
- Middleware `active` (signs out disabled users), `password.changed`, `role:<roles>`; guests redirect to admin.login, users to admin.dashboard (files: app/Http/Middleware/EnsureUserIsActive.php, app/Http/Middleware/EnsurePasswordIsChanged.php, app/Http/Middleware/EnsureUserHasRole.php, bootstrap/app.php)
- Gates manage-settings, manage-users, view-financials; UserPolicy (no self-disable/self-reset) (files: app/Providers/AppServiceProvider.php, app/Policies/UserPolicy.php)
- Owner-only users management: list, add with temporary password, edit (no own role change), enable/disable, reset password (files: app/Http/Controllers/Admin/UserController.php, app/Http/Requests/Admin/StoreUserRequest.php, app/Http/Requests/Admin/UpdateUserRequest.php, app/Http/Requests/Admin/ResetUserPasswordRequest.php, app/Services/UserService.php, resources/views/admin/users/*.blade.php)
- Owner-only settings screen, one tab per group; registry of keys/defaults/rules in SettingGroup; cached SettingService (files: app/Enums/SettingGroup.php, app/Services/SettingService.php, app/Http/Controllers/Admin/SettingController.php, app/Http/Requests/Admin/UpdateSettingsRequest.php, resources/views/admin/settings/edit.blade.php)
- ActivityLogger + ActivityAction enum (auth, user, settings actions) (files: app/Services/ActivityLogger.php, app/Enums/ActivityAction.php)
- Dashboard shell: pending, arrivals today, next 7 days, revenue this month (owner), upcoming list (files: app/Services/DashboardService.php, app/Http/Controllers/Admin/DashboardController.php, resources/views/admin/dashboard.blade.php)
- Tests: auth, access control, users, settings, dashboard, SettingService (files: tests/Feature/Admin/*.php, tests/Feature/Services/SettingServiceTest.php)
### Changed
- Admin layout: role-aware nav built from routes (only built modules listed), account menu with change password / sign out (files: resources/views/layouts/admin.blade.php, resources/views/partials/admin-sidebar.blade.php)
- x-ui.input/select/textarea accept bracketed names (`group[key]`) for old() and error lookup (files: resources/views/components/ui/input.blade.php, resources/views/components/ui/select.blade.php, resources/views/components/ui/textarea.blade.php)
- SettingSeeder seeds from SettingGroup::fields() and flushes the settings cache (files: database/seeders/SettingSeeder.php)
- User: must_change_password, last_login_at; factory state mustChangePassword() (files: app/Models/User.php, database/factories/UserFactory.php)
- Admin guide: signing in, users, dashboard, settings (files: docs/admin-guide.md)
### DB: added 2026_10_01_001400_add_security_columns_to_users_table (users.must_change_password, users.last_login_at)
### Routes: added admin.login, admin.login.store, admin.logout, admin.password.edit/update, admin.dashboard, admin.settings.index/edit/update, admin.users.index/create/store/edit/update, admin.users.toggle-active, admin.users.reset-password
### Breaking / Notes
- Run `php artisan migrate`. Re-running OwnerSeeder resets the owner password to OWNER_PASSWORD and forces a change on next login.

## [M0.1] Switch database engine to PostgreSQL - 2026-10-01
### Changed
- Default DB connection is `pgsql`; `.env.example` uses PostgreSQL placeholders (127.0.0.1:5432, db wonderpool, user wonderpool_user) (files: config/database.php, .env.example)
- Tests run on PostgreSQL database `wonderpool_test` instead of in-memory SQLite (files: phpunit.xml, tests/Pest.php)
- CI: `postgres:16` service with pg_isready health check, pdo_pgsql/pgsql extensions, DB_* env for Pest (files: .github/workflows/ci.yml)
- `json` columns → `jsonb` (files: database/migrations/2026_10_01_000400_create_pricing_rules_table.php, database/migrations/2026_10_01_001300_create_activity_logs_table.php)
- Emails stored trimmed + lowercase via mutators; OwnerSeeder normalizes the lookup email (files: app/Models/User.php, app/Models/Booking.php, database/seeders/OwnerSeeder.php)
- Docs: MySQL → PostgreSQL, §5.2 advisory-lock strategy + optional exclusion constraint, pg_dump backups, PostgreSQL 15+; database rules for agents; install steps with role/database SQL; D-014 (files: PLAN.md, CLAUDE.md, HISTORY.md, MILESTONES.md, README.md, docs/database.md, docs/deployment.md, docs/architecture.md)
- Ignore `*.dump` and `*.backup` (files: .gitignore)
### Added
- PostgreSQL compatibility tests: lowercase emails, owner seeder normalization, jsonb round-trip and column types (files: tests/Feature/Models/PostgresCompatibilityTest.php)
### DB: edited (not new) migrations create_pricing_rules_table and create_activity_logs_table (json → jsonb); run `php artisan migrate:fresh --seed`
### Routes: none
### Breaking / Notes
- Requires PostgreSQL 15+ and PHP pdo_pgsql/pgsql. Local `.env` must switch DB_CONNECTION/DB_PORT/DB_USERNAME/DB_PASSWORD; tests need database `wonderpool_test`.

## [M1] Data layer - 2026-10-01
### Added
- Enums with label()/color(): BookingStatus, PaymentStatus, PaymentType, UserRole, PricingRuleType, PricingAdjustmentType, GalleryCategory (files: app/Enums/*.php)
- Money centavo helper: fromPesos, toPesos, format (files: app/Support/Money.php)
- Migrations: users role/is_active; packages, add_ons, pricing_rules, bookings (soft deletes, window/status/phone indexes), booking_add_ons, payments, blocked_dates, amenities, gallery_images, faqs, settings, activity_logs (files: database/migrations/2026_10_01_000100…001300_*.php)
- Models with casts, relationships, PHPDoc, scopes (Booking::active/overlapping/status, BlockedDate::overlapping, Package/Amenity/Faq active+ordered, GalleryImage visible, PricingRule forPackage) and money accessors (files: app/Models/*.php)
- Factories for every model with PH sample data and states (files: database/factories/*.php, database/factories/Concerns/PhilippineData.php)
- Seeders: Owner (from .env), Package (DAY-A/NIGHT-D/24H), Amenity (8), Setting, Faq (files: database/seeders/*.php, config/wonderpool.php, .env.example)
- Tests: factories, overlap edge cases, seeders, enums, money (files: tests/Feature/Models/FactoriesTest.php, tests/Feature/Models/BookingScopesTest.php, tests/Feature/SeederTest.php, tests/Unit/MoneyTest.php, tests/Unit/EnumsTest.php, tests/Pest.php)
- ER diagram + table dictionary (files: docs/database.md)
### Changed
- Palette aligned with PLAN.md §7 (cyan/green); primary button and white-text surfaces moved to 700 shades for AA (files: resources/css/app.css, resources/views/components/ui/button.blade.php, resources/views/partials/head.blade.php, resources/views/home.blade.php, resources/views/layouts/public.blade.php)
- x-ui.badge takes a status enum (label/color from enum) instead of a string map (files: resources/views/components/ui/badge.blade.php, resources/views/design-preview.blade.php)
- User model: role/is_active casts, relationships, active scope; UserFactory owner/inactive states (files: app/Models/User.php, database/factories/UserFactory.php)
### Removed
- tests/Unit/.gitkeep (files: tests/Unit/.gitkeep)
### DB: added 2026_10_01_000100_add_role_and_is_active_to_users_table + 12 create_* migrations (packages, add_ons, pricing_rules, bookings, booking_add_ons, payments, blocked_dates, amenities, gallery_images, faqs, settings, activity_logs)
### Routes: none
### Breaking / Notes
- `x-ui.badge status="..."` strings no longer work; pass an enum (`:status="$booking->status"`).
- New env vars OWNER_NAME / OWNER_EMAIL / OWNER_PASSWORD (seeder skips owner when unset).

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
