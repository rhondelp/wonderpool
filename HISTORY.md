# HISTORY — Project Memory

> Keep under ~500 lines. Edit affected lines only. Every entry references a milestone ID.

## Project Summary
- Wonderpool Garden Resort booking system: public booking site + admin panel. (M0)
- Scope and roadmap: PLAN.md (§12 = roadmap). Tracking: CHANGELOG.md, MILESTONES.md.

## Stack & Versions
| Component | Version | Milestone |
|---|---|---|
| PHP | 8.2 (local XAMPP 8.2.12; CI 8.2) | M0 |
| Laravel | 12.x (12.69) | M0 |
| PostgreSQL | 15+ (local 18, CI postgres:16); tests use `wonderpool_test` (D-014) | M0.1 |
| Tailwind CSS | 4.x (Vite plugin) + @tailwindcss/forms 0.5 | M0 |
| Alpine.js | 3.x + @alpinejs/focus (x-trap) | M0 |
| blade-heroicons | 2.7 | M0 |
| Font | Poppins 400/500/600/700 via @fontsource/poppins 5 (font-display: swap) | M0 |
| Pest | 3.x + pest-plugin-laravel | M0 |
| Larastan | 3.x, level 6 | M0 |
| Pint | 1.x, preset psr12 | M0 |
| Vite | 7.x (laravel-vite-plugin 2) | M0 |

## Conventions & Decisions
| ID | Date | Decision | Reason | Milestone |
|---|---|---|---|---|
| D-000 | 2026-10-01 | Repo `https://github.com/rhondelp/wonderpool.git` (PUBLIC), default branch `main`. Bootstrap commit only on main; then one branch per phase `phase/M<id>-<slug>`, post-launch `change/<slug>`; Conventional Commits with milestone ID; PR into main, reviewed/merged by owner only; no force-push/history rewrite. | Reviewable phases; public repo requires strict secret hygiene. | M0 |
| D-001 | 2026-10-01 | Money = integer centavos (`unsignedInteger`, max ≈ ₱42.9M; signed `integer` only for deltas), column suffix `_cents` (e.g. `total_amount_cents`); convert/format only via `App\Support\Money` (M1). | Exact arithmetic (nights × rate, discounts, deposits) with no float/rounding drift; ints are cheap to SUM in reports; no string-decimal casts. | M0 |
| D-002 | 2026-10-01 | Tailwind v4 CSS-first config: tokens live in `resources/css/app.css` `@theme` (no tailwind.config.js); forms plugin via `@plugin`. This file IS "the tailwind config". | Laravel 12 ships Tailwind v4; avoids a legacy JS config. | M0 |
| D-003 | 2026-10-01 | Palette = PLAN.md §7: `pool-*` = Tailwind cyan, `garden-*` = Tailwind green (missing 200/400/800/950 shades filled from the same scales). Neutrals = slate; warning = amber; danger = rose. | Reconciled with PLAN.md in M1 (was provisional in M0). | M1 |
| D-004 | 2026-10-01 | Badge colors come from enum `color()`: BookingStatus pending=amber, approved=garden, rejected=rose, completed=pool, cancelled=slate; PaymentStatus pending=amber, verified=garden, rejected=rose. `x-ui.badge :status` takes the enum. | PLAN.md §7; single source of truth in enums. | M1 |
| D-005 | 2026-10-01 | Layouts use `@extends`/`@yield` (`layouts.public`, `layouts.admin`) with shared `partials.head`; stacks `styles` and `scripts`; flash via `x-ui.flash` reading session keys success/error/warning/info. | Simple, familiar Blade inheritance; one place for meta tags. | M0 |
| D-006 | 2026-10-01 | Tests: Pest on PostgreSQL database `wonderpool_test` (phpunit.xml) with RefreshDatabase; `Tests\TestCase::setUp` calls `withoutVite()`. | Tests exercise the real engine (jsonb, case-sensitive comparisons); was SQLite until M0.1. | M0.1 |
| D-007 | 2026-10-01 | `/design-preview` route registered only when `APP_ENV=local`. REMOVE IN M9. | Visual QA of theme/components without exposing it in prod. | M0 |
| D-008 | 2026-10-01 | White text only on `pool-700+` / `garden-700+` (primary button = `bg-pool-700 hover:bg-pool-800`), not PLAN's `pool-600`. | White on pool-600 (#0891b2) is ~3.7:1, fails WCAG AA; pool-700 is ~5.4:1. | M1 |
| D-009 | 2026-10-01 | Datetimes stored as local Asia/Manila wall-clock (app timezone), no UTC conversion. Booking windows are half-open `[starts_at, ends_at)`; overlap = `starts_at < E AND ends_at > S` (touching ≠ overlap). | Single-location resort; PLAN.md §5.1. | M1 |
| D-010 | 2026-10-01 | Enums stored as `string(20)` columns (not native DB enum types) and cast in models. | Adding a case needs no ALTER TABLE; values validated by PHP enums. | M1 |
| D-011 | 2026-10-01 | FK delete rules: package/add-on → `restrict` (keep history), owned children (pricing_rules, booking_add_ons, payments) → `cascade`, actor columns (approved_by, verified_by, created_by, activity_logs.user_id) → `set null`. | History preserved; deleting a user never deletes bookings. | M1 |
| D-012 | 2026-10-01 | Settings keys are dotted `group.name`; `SettingSeeder` uses firstOrCreate (never overwrites admin edits). Owner credentials come only from `.env` via `config('wonderpool.owner.*')`. | Safe re-seeding; no credentials in git. | M1 |
| D-013 | 2026-10-01 | Guest phone stored in E.164 (`+639XXXXXXXXX`), indexed for track-booking lookup. | One canonical format for lookups/SMS later. | M1 |
| D-015 | 2026-10-01 | Admin-only accounts at `/admin/*` (guests never log in). Forgotten passwords are reset by an owner with a temporary password (`must_change_password` forces a change); no email reset flow until mail exists (M8). Owner seeded from .env must change that password on first login. Login throttled 5 attempts/min per email+IP. | PLAN.md §10 (forced owner password change, rate-limited login); mail not configured yet. | M2 |
| D-016 | 2026-10-01 | Roles enforced twice: route groups use `role:owner` middleware; views/requests use gates `manage-settings`, `manage-users`, `view-financials` and `UserPolicy`. Nobody can disable, reset or change the role of themselves (keeps ≥1 active owner). Admin nav lists only built modules, filtered by gate. | Staff = bookings only (PLAN.md §2.2); defense in depth. | M2 |
| D-017 | 2026-10-01 | Settings registry = `SettingGroup::fields()` (label, input type, default, validation rules per key). `SettingService` caches all rows under `settings.all` forever, flushes on write, falls back to declared defaults; SettingSeeder seeds from the registry. Empty optional values stored as `""`. Settings forms post nested inputs `group[name]`. | One source of truth for keys, defaults and validation. | M2 |
| D-014 | 2026-10-01 | Database engine is PostgreSQL. Switched from MySQL on 2026-10-01 because MySQL does not run on the dev machine. JSON columns are jsonb (no key-order guarantee); emails stored lowercase via User/Booking mutators; booking creation to be serialized with `pg_advisory_xact_lock` (PLAN.md §5.2). | Local MariaDB unusable; PostgreSQL available locally and in CI. | M0.1 |

## Folder Map
| Path | Purpose | Milestone |
|---|---|---|
| `/` | CLAUDE.md, PLAN.md, HISTORY.md, CHANGELOG.md, MILESTONES.md, README.md, pint.json, phpstan.neon | M0 |
| `.github/workflows/ci.yml` | CI: Pint, Larastan, npm build, Pest | M0 |
| `app/` | Laravel app code | M0 |
| `app/Enums/` | String-backed enums with label()/color() | M1 |
| `app/Http/Controllers/Admin/` | Admin controllers; `Auth/` = Login, Password | M2 |
| `app/Http/Middleware/` | EnsureUserIsActive (`active`), EnsurePasswordIsChanged (`password.changed`), EnsureUserHasRole (`role`); aliases in bootstrap/app.php | M2 |
| `app/Http/Requests/Admin/` | Admin Form Requests (login, passwords, users, settings) | M2 |
| `app/Policies/` | UserPolicy | M2 |
| `app/Providers/AppServiceProvider.php` | Service singletons + gates (manage-settings, manage-users, view-financials) | M2 |
| `app/Services/` | Business logic (see Services Index) | M2 |
| `app/Models/` | Eloquent models (see Models & Relationships) | M1 |
| `app/Support/Money.php` | Centavo convert/format helpers (D-001) | M1 |
| `config/app.php` | timezone = env APP_TIMEZONE (Asia/Manila) | M0 |
| `config/wonderpool.php` | App config: `owner.name/email/password` from env | M1 |
| `database/migrations/2026_10_01_*` | M1 schema (users alter + 12 tables) | M1 |
| `database/factories/` | Factory per model; `Concerns/PhilippineData` (PH names, +639 mobiles) | M1 |
| `database/seeders/` | DatabaseSeeder → Owner, Package, Amenity, Setting, Faq seeders | M1 |
| `docs/` | architecture, database (ERD + dictionary, M1), booking-flow, admin-guide, deployment | M0 |
| `resources/css/app.css` | Tailwind v4 entry + `@theme` tokens + Poppins imports (D-002) | M0 |
| `resources/js/app.js` | Alpine + focus plugin bootstrap | M0 |
| `resources/views/layouts/` | `public.blade.php` (guest site), `admin.blade.php` (sidebar/drawer, `$nav` array, account menu), `auth.blade.php` (login / change password) | M0/M2 |
| `resources/views/admin/` | `dashboard`, `auth/{login,change-password}`, `users/{index,create,edit}`, `settings/edit` | M2 |
| `resources/views/partials/` | `head` (meta, vite, styles stack), `admin-sidebar` (nav list) | M0 |
| `resources/views/components/ui/` | Shared UI components (x-ui.*) | M0 |
| `resources/views/components/admin/` | Admin-only components (x-admin.*) | M0 |
| `resources/views/home.blade.php` | Temporary landing page (replace in M5) | M0 |
| `resources/views/design-preview.blade.php` | Component gallery, local only (remove M9) | M0 |
| `routes/web.php` | Web routes | M0 |
| `tests/Feature/SmokeTest.php` | Boot/home/design-preview guard tests | M0 |
| `tests/Feature/Models/` | FactoriesTest, BookingScopesTest (overlap edge cases), PostgresCompatibilityTest (lowercase emails, jsonb) | M1/M0.1 |
| `tests/Feature/SeederTest.php` | Seed data, idempotency, owner env guard | M1 |
| `tests/Feature/Admin/` | AuthTest, AccessControlTest, UsersTest, SettingsTest, DashboardTest | M2 |
| `tests/Feature/Services/` | SettingServiceTest | M2 |
| `tests/Unit/` | MoneyTest, EnumsTest | M1 |

## Routes Table
| Method | URI | Name | Controller@action | Middleware | Milestone |
|---|---|---|---|---|---|
| GET | `/` | home | `Route::view` → `home` | web | M0 |
| GET | `/design-preview` | design-preview | closure → `design-preview` (local env only; REMOVE IN M9) | web | M0 |
| GET | `/up` | — | Laravel health check | — | M0 |
| GET/POST | `/admin/login` | admin.login / admin.login.store | Admin\Auth\LoginController@create/store | guest | M2 |
| POST | `/admin/logout` | admin.logout | Admin\Auth\LoginController@destroy | auth, active | M2 |
| GET/PUT | `/admin/password` | admin.password.edit / .update | Admin\Auth\PasswordController@edit/update | auth, active | M2 |
| GET | `/admin` | admin.dashboard | Admin\DashboardController (invokable) | auth, active, password.changed | M2 |
| GET | `/admin/settings` | admin.settings.index | redirect → /admin/settings/general | + role:owner | M2 |
| GET/PUT | `/admin/settings/{group}` | admin.settings.edit / .update | Admin\SettingController@edit/update ({group} = SettingGroup) | + role:owner | M2 |
| resource | `/admin/users` (except show, destroy) | admin.users.* | Admin\UserController | + role:owner | M2 |
| PATCH | `/admin/users/{user}/active` | admin.users.toggle-active | Admin\UserController@toggleActive | + role:owner | M2 |
| PUT | `/admin/users/{user}/password` | admin.users.reset-password | Admin\UserController@resetPassword | + role:owner | M2 |

## Database Tables
| Table | Key columns | Relations | Milestone |
|---|---|---|---|
| users | email UK, role, is_active, must_change_password (M2), last_login_at (M2) | → bookings (approved_by), payments (verified_by), blocked_dates (created_by), activity_logs | M1 (alter) |
| packages | code UK, base_price_cents, start_time, end_time, crosses_midnight, max_pax, is_active, sort_order | → bookings (restrict), pricing_rules (cascade) | M1 |
| add_ons | price_cents, is_active | → booking_add_ons (restrict) | M1 |
| pricing_rules | package_id?, type, starts_on, ends_on, days_of_week json, adjustment_type, adjustment_value, priority | package (null = global) | M1 |
| bookings | reference_code UK, package_id, starts_at, ends_at, total_amount_cents, downpayment_required_cents, status, approved_by; soft deletes; IDX (starts_at,ends_at), status, guest_phone | package, approver, booking_add_ons, payments | M1 |
| booking_add_ons | booking_id, add_on_id (UK pair), quantity, unit_price_cents | booking (cascade), add_on (restrict) | M1 |
| payments | booking_id, type, amount_cents, proof_path, status, reference_no, verified_by | booking (cascade), verifier (set null) | M1 |
| blocked_dates | starts_at, ends_at, reason, created_by | creator (set null) | M1 |
| amenities | name, icon (heroicon), image_path, is_active, sort_order | — | M1 |
| gallery_images | path, caption, category, is_visible, sort_order | — | M1 |
| faqs | question, answer, sort_order, is_active | — | M1 |
| settings | key UK, value text, group | — | M1 |
| activity_logs | user_id?, action, subject morph, properties json, created_at only | user (set null), subject (morph) | M1 |

Full dictionary + ERD: `docs/database.md`.

## Models & Relationships
| Model | Relationships | Scopes / helpers | Milestone |
|---|---|---|---|
| User | approvedBookings (hasMany Booking), activityLogs | active(); isOwner(); casts role→UserRole, must_change_password, last_login_at; email mutator lowercases (D-014) | M1/M2 |
| Package | bookings, pricingRules | active(), ordered(); formatted_price | M1 |
| AddOn | bookings (belongsToMany via BookingAddOn) | active(); formatted_price | M1 |
| BookingAddOn (Pivot) | booking, addOn | line_total_cents, formattedLineTotal() | M1 |
| PricingRule | package | active(), forPackage($id) | M1 |
| Booking | package, approver (User), addOns (pivot quantity/unit_price_cents), payments | active() (pending+approved), overlapping($s,$e), status($enum); formatted_total, formatted_downpayment; guest_email mutator lowercases (D-014); SoftDeletes | M1 |
| Payment | booking, verifier (User) | verified(); formatted_amount | M1 |
| BlockedDate | creator (User) | overlapping($s,$e) | M1 |
| Amenity | — | active(), ordered() | M1 |
| GalleryImage | — | visible(), ordered() | M1 |
| Faq | — | active(), ordered() | M1 |
| Setting | — | — (read/write only via SettingService, D-017) | M1 |
| ActivityLog | user, subject (morphTo) | UPDATED_AT = null | M1 |

## Enums
| Name | Cases | Milestone |
|---|---|---|
| BookingStatus | pending, approved, rejected, cancelled, completed; label(), color(), static blocking() = [pending, approved] | M1 |
| PaymentStatus | pending, verified, rejected; label(), color() | M1 |
| PaymentType | downpayment, balance, full; label() | M1 |
| UserRole | owner, staff; label(), color() | M1 |
| PricingRuleType | weekend, holiday, season; label() | M1 |
| PricingAdjustmentType | percent (whole % points), fixed (signed centavos); label() | M1 |
| GalleryCategory | pools, rooms, hall, events; label() | M1 |
| ActivityAction | auth.login/logout/password_changed, user.created/updated/activated/deactivated/password_reset, settings.updated; label(); add cases per module | M2 |
| SettingGroup | general, booking, payment, contact, social; label(), icon(), fields() = settings registry (D-017) | M2 |

## Services Index
| Class | Public methods (purpose) | Milestone |
|---|---|---|
| ActivityLogger | log(ActivityAction, ?Model subject, array properties, ?User actor) → ActivityLog | M2 |
| SettingService | get(key, default), int(key, default), group(SettingGroup), update(SettingGroup, values) → changes (logged), all(), flush(), static declaredDefault(key) | M2 |
| UserService | create, update, setActive, resetPassword (temp + force change), changeOwnPassword, recordLogin, recordLogout; all audited | M2 |
| DashboardService | summary(?now) → pending/arrivals_today/upcoming_week/revenue_month_cents; upcoming(limit, ?now) | M2 |

## Blade Components
| Tag | Props | Used in | Milestone |
|---|---|---|---|
| `x-ui.button` | variant(primary/secondary/danger/ghost), size(sm/md/lg), type, href, icon | layouts, design-preview | M0 |
| `x-ui.input` | name* (may be `group[key]`, D-017), label, type, id, value, hint, required | admin forms, design-preview | M0/M2 |
| `x-ui.select` | name* (may be `group[key]`), options[value=>label], label, id, selected, placeholder, hint, required | admin users, design-preview | M0/M2 |
| `x-ui.textarea` | name* (may be `group[key]`), label, id, value, rows, hint, required | admin settings, design-preview | M0/M2 |
| `x-ui.card` | title, padded; slots actions, footer | design-preview | M0 |
| `x-ui.badge` | status (BookingStatus/PaymentStatus/UserRole enum → label/color, D-004), color(pool/garden/amber/rose/slate) | design-preview | M1 |
| `x-ui.modal` | name*, title, maxWidth(sm/md/lg/xl), show; slot footer; events open-modal/close-modal | design-preview | M0 |
| `x-ui.alert` | type(success/error/warning/info), title, dismissible | x-ui.flash, design-preview | M0 |
| `x-ui.flash` | — (reads session success/error/warning/info) | both layouts | M0 |
| `x-ui.empty-state` | title, description, icon; default slot = CTA | design-preview | M0 |
| `x-admin.stat-card` | label*, value*, icon, color(pool/garden/amber/rose), hint | design-preview | M0 |
| `x-admin.page-header` | title*, description; slot actions | design-preview | M0 |

## Settings Keys
Defaults, labels and validation live in `SettingGroup::fields()` (D-017); this table documents them.

| Key | Group | Default | Meaning | Milestone |
|---|---|---|---|---|
| general.resort_name | general | Wonderpool Garden Resort | Display name | M1 |
| booking.downpayment_percent | booking | 50 | % of total required as downpayment | M1 |
| booking.pending_hold_hours | booking | 24 | Hours before an unpaid pending booking expires (§5.3) | M1 |
| booking.lead_time_hours | booking | 24 | Minimum hours between booking and start | M1 |
| booking.max_advance_days | booking | 365 | How far ahead guests may book | M1 |
| payment.instructions | payment | GCash/bank PLACEHOLDER text | Shown on booking step 3 | M1 |
| contact.phone | contact | +63 9XX XXX XXXX (placeholder) | Public contact number | M1 |
| contact.email | contact | info@example.com (placeholder) | Public contact email | M1 |
| contact.address | contact | placeholder | Resort address | M1 |
| contact.map_embed_url | contact | "" | Google Maps embed URL | M1 |
| social.facebook_url | social | https://www.facebook.com/ (placeholder) | Facebook page | M1 |
| social.instagram_url | social | "" | Instagram page | M1 |

## Scheduled Commands & Jobs
| Command/Job | Schedule | Purpose | Milestone |
|---|---|---|---|

## Notifications & Mail Templates
| Class | Channel(s) | Trigger | Template | Milestone |
|---|---|---|---|---|

## Env Variables
| Name | Purpose | Milestone |
|---|---|---|
| APP_NAME | "Wonderpool Garden Resort" | M0 |
| APP_ENV | local/production; `local` enables /design-preview | M0 |
| APP_DEBUG | false in production | M0 |
| APP_URL | Base URL (default http://localhost:8000) | M0 |
| APP_TIMEZONE | App timezone, read by config/app.php (Asia/Manila) | M0 |
| DB_CONNECTION/HOST/PORT/DATABASE/USERNAME/PASSWORD | PostgreSQL connection: `pgsql`, 127.0.0.1:5432, db `wonderpool`, user `wonderpool_user` (tests: db `wonderpool_test`) | M0.1 |
| SESSION_DRIVER, CACHE_STORE, QUEUE_CONNECTION | `database` (needs migrations) | M0 |
| MAIL_* | Mailer; `log` in dev | M0 |
| OWNER_NAME | Initial owner display name (OwnerSeeder) | M1 |
| OWNER_EMAIL | Initial owner login email; seeder skips if empty | M1 |
| OWNER_PASSWORD | Initial owner password (.env only, never committed); seeder skips if empty; must be changed on first login (D-015) | M1/M2 |

## Business Flow Summaries
### Booking flow
- _tbd (M4/M5)_
### Status lifecycle
- States (M1 enum): pending → approved → completed; pending → rejected | cancelled; approved → cancelled. Proof upload keeps `pending` (payment status tracks verification). Transition guard in BookingService (M4).
### Pricing
- _tbd (M4)_ Data ready (M1): packages.base_price_cents, pricing_rules (priority asc), booking_add_ons.unit_price_cents snapshot.
### Availability
- Unavailable if any `Booking::active()->overlapping(S, E)` or `BlockedDate::overlapping(S, E)` (M1 scopes; half-open, D-009). Service + locking in M4.

## Known Issues / TODO
- Resolved in M0.1: local MariaDB was unusable, so the project moved to PostgreSQL (D-014); schema verified with migrate:fresh --seed, rollback and re-migrate on PostgreSQL 18. (M0/M1/M0.1)
- Settings contact/payment/social values are placeholders; replace once the owner answers PLAN.md §14 Q5. (M1)
- Day package hours (7AM–5PM) and Day max pax (50) are proposed defaults pending owner confirmation (PLAN.md §1). (M1)
- Public nav links are `#` placeholders until routes exist (M5). Admin nav lists built modules only; add Bookings/Content/Reports entries to `$nav` in layouts/admin.blade.php as they ship. (M0/M2)
- No self-service "forgot password" email yet; owner resets passwords (D-015). Revisit in M8. (M2)
- Dashboard charts/occupancy deferred to M7. (M2)
- Remove `/design-preview` route + view in M9 (D-007). (M0)
