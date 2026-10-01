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
| intervention/image | 3.11 (GD driver; PHP `gd` with WebP required, CI enables it) | M3 |
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
| D-018 | 2026-10-01 | Content images live on the `public` disk (`storage/app/public`, served at `/storage` after `php artisan storage:link`): original at `{dir}/originals/{uuid}.{ext}`, WebP `{dir}/large/{uuid}.webp` (≤1600 px) and `{dir}/thumbs/{uuid}.webp` (≤480 px); dir = `gallery` or `amenities`. DB stores only the original's path; variant paths derived by `ImageService::variantPath()`. Uploads: jpg/png/webp, ≤5 MB, ≤8000 px/side; gallery ≤20 files per upload. Files deleted with the record or on replacement. Payment proofs are NOT here (private disk, M5). | Fast pages (thumbs/WebP) while keeping originals; unguessable UUID names. | M3 |
| D-019 | 2026-10-01 | Content modules share `ContentService` (create/update/delete/reorder) and log `content.created/updated/deleted/reordered` with `{type, label}` (+ changed field names on update). Deletion guarded by `GuardsDeletion`: packages with any booking (incl. soft-deleted) and add-ons on any booking cannot be deleted → warning flash suggesting deactivation. | Bookings reference packages/add-ons with restrict FKs (D-011); one audit format. | M3 |
| D-020 | 2026-10-01 | Display order: `sort_order` renumbered 1..n on every reorder. Reorder endpoints receive the visible ids (page/filter subset); those records swap among the slots they already hold in the full order. New records go last (max+1); blank sort_order on update keeps the current value. Admin search uses ILIKE with `%`/`_` escaped; filter `status=active|inactive` maps to `is_active` (gallery: `is_visible`). | Works with pagination and filters; repairs duplicate orders. | M3 |
| D-021 | 2026-10-01 | Double-booking defense: every window-claiming write (BookingService::create/reschedule) runs in `DB::transaction` and first takes `pg_advisory_xact_lock(BookingService::BOOKING_LOCK_KEY = 7461001)`, then re-checks availability with plain `exists()` queries (never lockForUpdate + aggregates). Second line: partial exclusion constraint `bookings_no_overlap` (`EXCLUDE USING gist (tsrange(starts_at, ends_at, '[)') WITH &&) WHERE status IN ('pending','approved') AND deleted_at IS NULL`); SQLSTATE 23P01 → SlotUnavailableException. Blocked dates are enforced by the service only. Saving a blocked date never cancels bookings; it warns with their references. | Exclusive-use resort: row locks cannot stop two requests that both see no overlap. | M4 |
| D-022 | 2026-10-01 | Pricing: base → every matching active rule (global or this package; date within [starts_on, ends_on], nulls open; ISO weekday in days_of_week if set) in `priority` asc, then id, each applied to the running package price (percent compounds) → + add-ons (never adjusted). Percent delta rounded half away from zero to the centavo; running price floored at 0; downpayment = round half up of total × `booking.downpayment_percent`. Rules match on the booking's start date. Bookings snapshot totals and add-on unit prices; reschedule keeps the snapshot unless `reprice`. | Owner chose "stack in priority order" (M4 plan). | M4 |
| D-023 | 2026-10-01 | Reference codes `WP-{YYMM of stay}-{4 chars}` from `ABCDEFGHJKMNPQRSTUVWXYZ23456789` (no 0/O/1/I/L); unique vs all bookings incl. soft-deleted; generated under the booking advisory lock, max 10 attempts. | Readable over the phone; collision-safe. | M4 |
| D-024 | 2026-10-01 | Pending-hold expiry: `bookings:expire-stale` (every 15 min, withoutOverlapping) cancels `pending` bookings whose `created_at` ≤ now − `booking.pending_hold_hours` and that have no payment with a `proof_path`; logged as `booking.expired` with actor null. Lead time / max advance are checked by guest-facing requests (`AvailabilityService::isWithinBookingWindow`), not by `create()`, so admins can record walk-ins. | PLAN.md §5.3. | M4 |
| D-025 | 2026-10-01 | Guest identity = reference code + mobile number. Codes compared trimmed/uppercased with inner spaces removed; phones normalized to `+639XXXXXXXXX` by `App\Support\PhoneNumber` before storing and comparing (PostgreSQL is case-sensitive). Wrong code or phone → one generic error. Booking pages `/book/{ref}`, `/book/{ref}/done`, `/track/{ref}` and proof upload are shown only to a browser whose session lists the booking id (`GuestBookingService::SESSION_KEY`, set on create or successful lookup); otherwise 404 / redirect to /track. Guests get lead time / max advance checks in `GuestBookingService::book()`. | PLAN.md §10 (no guessing); no guest accounts. | M5 |
| D-026 | 2026-10-01 | Payment proofs: jpg/png/webp/pdf ≤ 5 MB (extension + real MIME). Stored ONLY on the private `local` disk at `storage/app/private/payment-proofs/{booking_id}/{uuid}.{jpg|pdf}` (never `public`). Images re-encoded to JPEG q85, longest edge ≤ 2000 px, which drops EXIF/GPS; undecodable images → friendly error. PDFs stored as uploaded (GD cannot re-encode them). One current proof per booking: re-upload replaces the pending downpayment Payment's file (old file deleted after commit); uploads allowed only while the booking is pending; logged `payment.proof_uploaded`. Admin viewing route arrives in M6. | PLAN.md §5.6. | M5 |
| D-027 | 2026-10-01 | Public content comes only from DB/settings: new SettingGroups `content` (tagline, hero, about, highlights, house rules, cancellation policy, booking success note) and `seo` (meta description, share image; fallback = first visible gallery image). Long owner text rendered by `x-ui.prose` with `Str::markdown(html_input=strip, allow_unsafe_links=false)`. Empty settings/collections hide their section (`x-ui.section :when`). `$site` shared to `layouts.public`, `partials.head`, `public.*` by a view composer. | No hard-coded rates or text; safe owner formatting. | M5 |
| D-028 | 2026-10-01 | Anti-abuse: named limiters per IP — `booking-read` 60/min (calendar, quote), `booking-write` 5/min + 20/day (POST /book), `proof-upload` 10/min, `track` 10/min (POST /track); honeypot field `website` must be empty; Cloudflare Turnstile behind `config('wonderpool.turnstile.enabled')` (env TURNSTILE_ENABLED, default false) via implicit rule `App\Rules\Turnstile`. The booking page JS only displays server JSON; price/availability are never computed client-side. | PLAN.md §10. | M5 |
| D-014 | 2026-10-01 | Database engine is PostgreSQL. Switched from MySQL on 2026-10-01 because MySQL does not run on the dev machine. JSON columns are jsonb (no key-order guarantee); emails stored lowercase via User/Booking mutators; booking creation to be serialized with `pg_advisory_xact_lock` (PLAN.md §5.2). | Local MariaDB unusable; PostgreSQL available locally and in CI. | M0.1 |

## Folder Map
| Path | Purpose | Milestone |
|---|---|---|
| `/` | CLAUDE.md, PLAN.md, HISTORY.md, CHANGELOG.md, MILESTONES.md, README.md, pint.json, phpstan.neon | M0 |
| `.github/workflows/ci.yml` | CI: Pint, Larastan, npm build, Pest | M0 |
| `app/` | Laravel app code | M0 |
| `app/Enums/` | String-backed enums with label()/color() | M1 |
| `app/Exceptions/ContentInUseException.php` | Delete refused (message shown to admins) | M3 |
| `app/Exceptions/Booking/` | BookingException base + SlotUnavailable, GuestCountExceeded, InvalidAddOn, PackageUnavailable, InvalidStatusTransition, ReferenceCodeExhausted (M4); BookingWindow, PaymentProofNotAllowed, InvalidPaymentProof (M5) — messages safe to show | M4/M5 |
| `app/Events/BookingStatusChanged.php` | Fired after each committed status change (listeners in M8) | M4 |
| `app/Console/Commands/ExpireStaleBookings.php` | `bookings:expire-stale {--dry-run}` | M4 |
| `app/Http/Controllers/Admin/` | Admin controllers; `Auth/` = Login, Password; content: Package, AddOn, Amenity, Gallery, Faq (M3) | M2/M3 |
| `app/Http/Controllers/Public/` | PageController (pages, sitemap, robots), BookingController (book, calendar, quote, store, payment, proof, done), TrackBookingController | M5 |
| `app/Http/Requests/Public/` | QuoteRequest, StoreBookingRequest (extends Quote), UploadPaymentProofRequest, TrackBookingRequest | M5 |
| `app/Rules/` | PhilippineMobile, Turnstile (implicit, off by default) | M5 |
| `app/Http/Middleware/` | EnsureUserIsActive (`active`), EnsurePasswordIsChanged (`password.changed`), EnsureUserHasRole (`role`); aliases in bootstrap/app.php | M2 |
| `app/Http/Requests/Admin/` | Admin Form Requests (login, passwords, users, settings) | M2 |
| `app/Http/Requests/Admin/Content/` | `{Module}Request` base (rules + payload()) with Store/Update subclasses; StoreGalleryImagesRequest, UpdateGalleryImageRequest, ReorderRequest, ImageRules | M3 |
| `app/Policies/` | UserPolicy; ContentPolicy base + Package/AddOn/Amenity/GalleryImage/Faq policies (owner only) | M2/M3 |
| `app/Providers/AppServiceProvider.php` | Service singletons (+ ImageManager GD) + gates (manage-settings, manage-users, manage-content, view-financials) | M2/M3 |
| `app/Services/` | Business logic (see Services Index); `Content/` = content modules + images; `Booking/` = availability, pricing, booking lifecycle, blocked dates | M2/M3/M4 |
| `app/Models/Concerns/AdminListable.php` | search() ILIKE, whereState(), adminLabel(), adminSearchColumns() | M3 |
| `app/Models/Contracts/GuardsDeletion.php` | deletionBlockedReason() | M3 |
| `app/Support/AmenityIcons.php` | Curated heroicons for the amenity picker | M3 |
| `app/Support/PhoneNumber.php` | normalize() → +639XXXXXXXXX, display() (D-025) | M5 |
| `app/Models/` | Eloquent models (see Models & Relationships) | M1 |
| `app/Support/Money.php` | Centavo convert/format helpers (D-001) | M1 |
| `config/app.php` | timezone = env APP_TIMEZONE (Asia/Manila) | M0 |
| `config/wonderpool.php` | App config: `owner.name/email/password` from env | M1 |
| `database/migrations/2026_10_01_*` | M1 schema (users alter + 12 tables) | M1 |
| `database/factories/` | Factory per model; `Concerns/PhilippineData` (PH names, +639 mobiles) | M1 |
| `database/seeders/` | DatabaseSeeder → Owner, Package, Amenity, Setting, Faq seeders | M1 |
| `docs/` | architecture, database (ERD + dictionary, M1), booking-flow, admin-guide, deployment | M0 |
| `resources/css/app.css` | Tailwind v4 entry + `@theme` tokens + Poppins imports (D-002) | M0 |
| `resources/js/app.js` | Alpine + focus plugin bootstrap; registers `sortable` | M0/M3 |
| `resources/js/sortable.js` | Alpine drag-and-drop/keyboard reorder → PATCH {ids} | M3 |
| `resources/js/booking.js` | Alpine `bookingForm`: steps, calendar fetch, debounced quote fetch (display only) | M5 |
| `resources/views/layouts/` | `public.blade.php` (guest site: `$nav`, footer from `$site`), `admin.blade.php` (sidebar/drawer, `$nav` array, account menu), `auth.blade.php` (login / change password) | M0/M2/M5 |
| `resources/views/public/` | home, amenities, packages, gallery, faq, contact, policies, sitemap; `book/{index,payment,done,_summary,_proof-form}`; `track/{index,show}` | M5 |
| `storage/app/private/payment-proofs/{booking_id}/` | Guest payment proofs (private disk, D-026; not in git) | M5 |
| `resources/views/admin/` | `dashboard`, `auth/{login,change-password}`, `users/{index,create,edit}`, `settings/edit`; `{packages,add-ons,amenities,faqs}/{index,create,edit,_form}`, `gallery/{index,create,edit}` (M3); `{blocked-dates,pricing-rules}/{index,create,edit,_form}` (M4) | M2/M3/M4 |
| `storage/app/public/{gallery,amenities}/{originals,large,thumbs}/` | Uploaded content images (D-018; not in git) | M3 |
| `resources/views/partials/` | `head` (meta, vite, styles stack), `admin-sidebar` (nav list) | M0 |
| `resources/views/components/ui/` | Shared UI components (x-ui.*) | M0 |
| `resources/views/components/public/` | Public-only components (x-public.*) | M5 |
| `resources/views/components/admin/` | Admin-only components (x-admin.*) | M0 |
| `resources/views/design-preview.blade.php` | Component gallery, local only (remove M9) | M0 |
| `routes/web.php` | Web routes | M0 |
| `routes/console.php` | Schedule: bookings:expire-stale every 15 min | M4 |
| `tests/Feature/SmokeTest.php` | Boot/home/design-preview guard tests | M0 |
| `tests/Feature/Models/` | FactoriesTest, BookingScopesTest (overlap edge cases), PostgresCompatibilityTest (lowercase emails, jsonb) | M1/M0.1 |
| `tests/Feature/SeederTest.php` | Seed data, idempotency, owner env guard | M1 |
| `tests/Feature/Admin/` | AuthTest, AccessControlTest, UsersTest, SettingsTest, DashboardTest | M2 |
| `tests/Feature/Services/` | SettingServiceTest, ContentServiceTest (reorder, image paths) | M2/M3 |
| `tests/Feature/Admin/Content/` | PackagesTest, AddOnsTest, AmenitiesTest, GalleryTest, FaqsTest, ContentAccessTest | M3 |
| `tests/Feature/Booking/` | AvailabilityServiceTest, PricingServiceTest, ReferenceCodeGeneratorTest, BookingServiceTest (lock, constraint, transitions, reschedule), ExpireStaleBookingsTest | M4 |
| `tests/Feature/Admin/Booking/` | BlockedDatesTest, PricingRulesTest | M4 |
| `tests/Feature/Public/` | PagesTest, BookingFlowTest, QuoteEndpointTest, TrackBookingTest, AntiAbuseTest | M5 |
| `tests/Unit/PhoneNumberTest.php` | Phone normalization | M5 |
| `tests/Pest.php` | Helpers dayPackage(), nightPackage(), fullDayPackage(), bookingData() | M4 |
| `tests/Unit/` | MoneyTest, EnumsTest | M1 |

## Routes Table
| Method | URI | Name | Controller@action | Middleware | Milestone |
|---|---|---|---|---|---|
| GET | `/` | home | Public\PageController@home | web | M5 |
| GET | `/amenities`, `/packages`, `/gallery`, `/faq`, `/contact`, `/policies` | amenities, packages, gallery, faq, contact, policies | Public\PageController@{name} | web | M5 |
| GET | `/sitemap.xml`, `/robots.txt` | sitemap, robots | Public\PageController@sitemap/robots (public/robots.txt removed) | web | M5 |
| GET | `/book` (?package=) | book | Public\BookingController@create | web | M5 |
| GET | `/book/availability` (?package_id&month=YYYY-MM) | book.availability | Public\BookingController@availability (JSON) | throttle:booking-read | M5 |
| POST | `/book/quote` | book.quote | Public\BookingController@quote (JSON) | throttle:booking-read | M5 |
| POST | `/book` | book.store | Public\BookingController@store | throttle:booking-write | M5 |
| GET | `/book/{booking:reference_code}` | book.payment | Public\BookingController@payment (session access) | web | M5 |
| POST | `/book/{booking:reference_code}/payment` | book.payment.store | Public\BookingController@uploadProof (session access; `return=track`) | throttle:proof-upload | M5 |
| GET | `/book/{booking:reference_code}/done` | book.done | Public\BookingController@done (session access) | web | M5 |
| GET/POST | `/track` | track / track.lookup | Public\TrackBookingController@create/lookup | POST: throttle:track | M5 |
| GET | `/track/{booking:reference_code}` | track.show | Public\TrackBookingController@show (session access) | web | M5 |
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
| resource | `/admin/packages` (except show) | admin.packages.* | Admin\PackageController | + role:owner | M3 |
| resource | `/admin/add-ons` (except show; param `{add_on}`) | admin.add-ons.* | Admin\AddOnController | + role:owner | M3 |
| resource | `/admin/amenities` (except show) | admin.amenities.* | Admin\AmenityController | + role:owner | M3 |
| resource | `/admin/gallery` (except show; param `{gallery}` = GalleryImage) | admin.gallery.* | Admin\GalleryController | + role:owner | M3 |
| resource | `/admin/faqs` (except show) | admin.faqs.* | Admin\FaqController | + role:owner | M3 |
| PATCH | `/admin/{packages,amenities,gallery,faqs}/reorder` (JSON `{ids}`; declared before resources) | admin.{module}.reorder | {Module}Controller@reorder | + role:owner | M3 |
| PATCH | `/admin/gallery/{gallery}/visibility` | admin.gallery.toggle-visibility | Admin\GalleryController@toggleVisibility | + role:owner | M3 |
| resource | `/admin/blocked-dates` (except show; param `{blocked_date}`) | admin.blocked-dates.* | Admin\BlockedDateController (index ?status=past|all, default upcoming) | + role:owner | M4 |
| resource | `/admin/pricing-rules` (except show; param `{pricing_rule}`) | admin.pricing-rules.* | Admin\PricingRuleController (index ?quote_package=&quote_date= quote check) | + role:owner | M4 |

## Database Tables
| Table | Key columns | Relations | Milestone |
|---|---|---|---|
| users | email UK, role, is_active, must_change_password (M2), last_login_at (M2) | → bookings (approved_by), payments (verified_by), blocked_dates (created_by), activity_logs | M1 (alter) |
| packages | code UK, base_price_cents, start_time, end_time, crosses_midnight, max_pax, is_active, sort_order | → bookings (restrict), pricing_rules (cascade) | M1 |
| add_ons | price_cents, is_active | → booking_add_ons (restrict) | M1 |
| pricing_rules | package_id?, type, starts_on, ends_on, days_of_week json, adjustment_type, adjustment_value, priority | package (null = global) | M1 |
| bookings | reference_code UK, package_id, starts_at, ends_at, total_amount_cents, downpayment_required_cents, status, approved_by; soft deletes; IDX (starts_at,ends_at), status, guest_phone; EXCLUDE `bookings_no_overlap` (D-021, M4) | package, approver, booking_add_ons, payments | M1/M4 |
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
| Package | bookings, pricingRules | active(), ordered(); formatted_price; AdminListable (name, code, description); GuardsDeletion (any booking incl. trashed) | M1/M3 |
| AddOn | bookings (belongsToMany via BookingAddOn) | active(); formatted_price; AdminListable (name, description); GuardsDeletion (any booking_add_ons row) | M1/M3 |
| BookingAddOn (Pivot) | booking, addOn | line_total_cents, formattedLineTotal() | M1 |
| PricingRule | package | active(), forPackage($id); AdminListable (name) | M1/M4 |
| Booking | package, approver (User), addOns (pivot quantity/unit_price_cents), payments | active() (pending+approved), overlapping($s,$e), status($enum); formatted_total, formatted_downpayment; guest_email mutator lowercases (D-014); SoftDeletes | M1 |
| Payment | booking, verifier (User) | verified(); formatted_amount | M1 |
| BlockedDate | creator (User) | overlapping($s,$e); AdminListable (reason; label = reason + date); isWholeDays() | M1/M4 |
| Amenity | — | active(), ordered(); AdminListable (name, description); imageUrl(ImageVariant) | M1/M3 |
| GalleryImage | — | visible(), ordered(); AdminListable (caption; state column is_visible; label = caption or "Image #id"); imageUrl(ImageVariant) | M1/M3 |
| Faq | — | active(), ordered(); AdminListable (question, answer; label = question ≤60 chars) | M1/M3 |
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
| ActivityAction | auth.login/logout/password_changed, user.created/updated/activated/deactivated/password_reset, settings.updated, content.created/updated/deleted/reordered (M3), booking.created/status_changed/rescheduled/expired (M4), payment.proof_uploaded (M5); label(); add cases per module | M2/M3/M4/M5 |
| ImageVariant | originals, large (1600), thumbs (480); maxEdge() (D-018) | M3 |
| SettingGroup | general, booking, payment, contact, social; label(), icon(), fields() = settings registry (D-017) | M2 |

## Services Index
| Class | Public methods (purpose) | Milestone |
|---|---|---|
| ActivityLogger | log(ActivityAction, ?Model subject, array properties, ?User actor) → ActivityLog | M2 |
| SettingService | get(key, default), int(key, default), group(SettingGroup), update(SettingGroup, values) → changes (logged), all(), flush(), static declaredDefault(key) | M2 |
| UserService | create, update, setActive, resetPassword (temp + force change), changeOwnPassword, recordLogin, recordLogout; all audited | M2 |
| DashboardService | summary(?now) → pending/arrivals_today/upcoming_week/revenue_month_cents; upcoming(limit, ?now) | M2 |
| Content\ContentService | create(class, data) (sortables appended), update(model, data), delete(model) (GuardsDeletion → ContentInUseException), reorder(class, ids) (D-020); all audited (D-019) | M3 |
| Content\ImageService | store(UploadedFile, dir) → original path, delete(?path), static variantPath(path, ImageVariant), static url(?path, ImageVariant) (D-018) | M3 |
| Content\AmenityService | create(data, ?image), update(amenity, data, ?image, removeImage), delete(amenity) | M3 |
| Content\GalleryService | upload(files, GalleryCategory, ?caption, visible) → list, update, toggleVisibility, delete | M3 |
| Booking\AvailabilityService | resolveWindow(Package, CarbonInterface $date): array{starts_at: CarbonImmutable, ends_at: CarbonImmutable}; isAvailable(CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): bool; conflicts($start, $end, ?$ignoreBookingId): array{bookings, blocks}; unavailableDates(CarbonInterface $from, CarbonInterface $to, ?Package $package = null): array<Y-m-d, array{available: bool, packages: array<int, bool>}>; isWithinBookingWindow(CarbonInterface $start, ?CarbonInterface $now = null): bool | M4 |
| Booking\PricingService | quote(Package, CarbonInterface $date, array $addOns = [id => qty], int $guestCount = 1): PriceBreakdown (@throws GuestCountExceeded, InvalidAddOn); matchingRules(Package, CarbonInterface): Collection<PricingRule>; applyRule(int $amountCents, PricingRule): int; static percentOf(int $cents, int $percent): int; ruleLabel(PricingRule): string | M4 |
| Booking\PriceBreakdown | readonly: packageId, date, guestCount, basePriceCents, adjustments[{rule_id,label,delta_cents,running_cents}], packageSubtotalCents, addOns[{add_on_id,name,quantity,unit_price_cents,line_total_cents}], addOnsTotalCents, totalCents, downpaymentPercent, downpaymentRequiredCents; toArray() | M4 |
| Booking\ReferenceCodeGenerator | generate(CarbonInterface $startsAt): string (@throws ReferenceCodeExhausted); static pattern(): string; constants PREFIX, ALPHABET, RANDOM_LENGTH, MAX_ATTEMPTS | M4 |
| Booking\BookingService | create(array $data, ?User $actor = null): Booking (@throws PackageUnavailable, SlotUnavailable, GuestCountExceeded, InvalidAddOn, ReferenceCodeExhausted); transition(Booking, BookingStatus $to, ?User $actor, ?string $reason = null): Booking (@throws InvalidStatusTransition); canTransition(Booking, BookingStatus): bool; reschedule(Booking, CarbonInterface $newDate, ?User $actor, ?Package $newPackage = null, bool $reprice = false): Booking (@throws InvalidStatusTransition, PackageUnavailable, SlotUnavailable, GuestCountExceeded, InvalidAddOn); expireStale(?CarbonInterface $now = null, bool $dryRun = false): int; const TRANSITIONS, BOOKING_LOCK_KEY | M4 |
| Booking\BlockedDateService | create(array $data, User $actor): array{block, conflicts}; update(BlockedDate, array): array{block, conflicts}; delete(BlockedDate); overlappingBookings(BlockedDate): Collection<Booking> | M4 |
| Booking\GuestBookingService | calendar(Package, CarbonInterface $month): array{month, label, days: array<Y-m-d, available|booked|closed>, has_previous, has_next}; quote(Package, CarbonInterface $date, int $guestCount, array $addOns = []): array{available, reason, window, breakdown}; book(array $data): Booking (@throws BookingWindow, PackageUnavailable, SlotUnavailable, GuestCountExceeded, InvalidAddOn, ReferenceCodeExhausted); find(string $reference, string $phone): ?Booking; static normalizeReference(string): string; timeline(Booking): list<array{at, label, note, tone}>; remember(Booking); canAccess(Booking): bool; paymentInstructions(): ?string; windowLabel($start, $end): string | M5 |
| Booking\PaymentProofService | store(Booking, UploadedFile, ?string $referenceNo = null): Payment (@throws PaymentProofNotAllowed, InvalidPaymentProof); constants DISK=local, DIRECTORY, MAX_EDGE, QUALITY (D-026) | M5 |
| PublicContentService | site(): array{name, tagline, phone, email, address, map_embed_url, facebook_url, instagram_url, meta_description, og_image_url}; text(key): ?string; highlights(): list<string>; packages(); addOns(); amenities(?limit); gallery(?limit); faqs() — active/visible only (D-027) | M5 |

## Blade Components
| Tag | Props | Used in | Milestone |
|---|---|---|---|
| `x-ui.button` | variant(primary/secondary/danger/ghost/danger-ghost), size(sm/md/lg), type, href, icon | layouts, design-preview | M0 |
| `x-ui.input` | name* (may be `group[key]`, D-017), label, type, id, value, hint, required | admin forms, design-preview | M0/M2 |
| `x-ui.select` | name* (may be `group[key]`), options[value=>label], label, id, selected, placeholder, hint, required | admin users, design-preview | M0/M2 |
| `x-ui.textarea` | name* (may be `group[key]`), label, id, value, rows, hint, required | admin settings, design-preview | M0/M2 |
| `x-ui.checkbox` | name*, label*, checked, id, hint (hidden "0" + checkbox "1") | content forms | M3 |
| `x-ui.file-input` | name*, label, accept (jpg/png/webp), multiple (→ name[] + per-file errors), hint, required | amenities, gallery | M3 |
| `x-ui.card` | title, padded; slots actions, footer | design-preview | M0 |
| `x-ui.badge` | status (BookingStatus/PaymentStatus/UserRole enum → label/color, D-004), color(pool/garden/amber/rose/slate) | design-preview | M1 |
| `x-ui.modal` | name*, title, maxWidth(sm/md/lg/xl), show; slot footer; events open-modal/close-modal | design-preview | M0 |
| `x-ui.alert` | type(success/error/warning/info), title, dismissible | x-ui.flash, design-preview | M0 |
| `x-ui.flash` | — (reads session success/error/warning/info) | both layouts | M0 |
| `x-ui.empty-state` | title, description, icon; default slot = CTA | design-preview | M0 |
| `x-admin.stat-card` | label*, value*, icon, color(pool/garden/amber/rose), hint | design-preview | M0 |
| `x-admin.page-header` | title*, description; slot actions | admin pages | M0 |
| `x-admin.sortable` | url* (reorder endpoint), axis (y list/table, x grid); items need `data-sortable-id` + `draggable="true"` | packages, amenities, gallery, faqs | M3 |
| `x-admin.sort-handle` | label* (screen-reader name); grip + move up/down buttons | inside x-admin.sortable items | M3 |
| `x-admin.confirm-delete` | action*, label*, name* (unique modal), warning, size | content indexes | M3 |
| `x-admin.index-filters` | placeholder, states [value=>label]; slot extra filters; GET q/status | content indexes | M3 |
| `x-admin.icon-picker` | icons* (AmenityIcons::all()), name (icon), selected, label | amenities form | M3 |
| `x-ui.wave-divider` | flip; color via text-* class (currentColor) | public layout, heroes | M5 |
| `x-ui.section` | title, intro, id, when (false = render nothing) | public pages | M5 |
| `x-ui.prose` | text (owner Markdown-lite, HTML stripped; renders nothing when blank) | home, policies, booking, track | M5 |
| `x-public.page-hero` | title*, subtitle; slot | inner public pages | M5 |
| `x-public.package-card` | package* | home, packages | M5 |
| `x-public.amenity-card` | amenity* | home, amenities | M5 |

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
| content.tagline | content | Pools · Gardens · Good times | Footer tagline | M5 |
| content.hero_title | content | Your private pool and garden escape | Home headline (required) | M5 |
| content.hero_subtitle | content | (booking pitch) | Home sub-headline | M5 |
| content.about | content | "" | Home "About" (Markdown-lite; blank hides) | M5 |
| content.highlights | content | 4 sample lines | One per line, max 6 shown | M5 |
| content.house_rules | content | PLACEHOLDER rules | Policies page (Markdown-lite) | M5 |
| content.cancellation_policy | content | "" | Policies page + booking step 3 (blank hides; §14 Q4) | M5 |
| content.booking_success_note | content | (next steps text) | Success page "What happens next" | M5 |
| seo.meta_description | seo | (default description) | `<meta name=description>` / og:description | M5 |
| seo.og_image_url | seo | "" | Share image; blank = first visible gallery photo | M5 |

## Scheduled Commands & Jobs
| Command/Job | Schedule | Purpose | Milestone |
|---|---|---|---|
| `bookings:expire-stale {--dry-run}` | every 15 min, withoutOverlapping (routes/console.php) | Cancel unpaid pending bookings after `booking.pending_hold_hours` (D-024) | M4 |

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
| TURNSTILE_ENABLED / TURNSTILE_SITE_KEY / TURNSTILE_SECRET_KEY | Cloudflare Turnstile on the booking form; off by default (D-028) | M5 |

## Business Flow Summaries
### Booking flow
1. Guest opens `/book` (optionally `?package=`): packages (active), add-ons, payment instructions, cancellation policy (Public\BookingController@create).
2. Step 1: picks a package → Alpine fetches `/book/availability` (GuestBookingService::calendar → AvailabilityService::unavailableDates + isWithinBookingWindow: available / booked / closed); picks a date and guest count → debounced POST `/book/quote` (GuestBookingService::quote → AvailabilityService + PricingService::quote()->toArray()) shows window, total and downpayment. Continue is enabled only when the quote says available.
3. Step 2: name, PH mobile, email, occasion, notes, add-on quantities (each change re-quotes).
4. Step 3: summary from the last server quote, payment instructions, policy, honeypot, optional Turnstile, terms → normal POST `/book` (StoreBookingRequest) → GuestBookingService::book (lead time / max advance) → BookingService::create (advisory lock, re-check, snapshot, reference code) → booking id remembered in session → redirect `/book/{ref}`. Errors (slot taken, window, pax) redirect back to `/book` with a flash and old input. Without JS the same form shows all steps and posts directly.
5. `/book/{ref}`: summary + how to pay → upload proof (UploadPaymentProofRequest → PaymentProofService::store, private disk) → `/book/{ref}/done`: reference code (copy button), next steps, track link.
6. Later: `/track` (code + phone, normalized) → `/track/{ref}`: status, guest-safe timeline (created, proof received, approved/rejected + reason, cancelled/expired, moved, completed), upload/replace proof while pending.
- Engine (M4): `BookingService::create()` → advisory lock → package active? → `resolveWindow` → `isAvailable` → `PricingService::quote` → insert pending booking + `booking_add_ons` snapshot + reference code → `booking.created` log. Admin approval UI in M6.
### Status lifecycle
- `BookingService::TRANSITIONS`: pending → approved | rejected | cancelled; approved → completed | cancelled; rejected, cancelled, completed are final. Proof upload keeps `pending` (payment status tracks verification).
- Preconditions: rejected needs a reason (stored in rejection_reason); approved stamps approved_by/approved_at; completed only after `ends_at`. Only pending/approved bookings hold the slot and can be rescheduled.
- Every change: `booking.status_changed` activity (from, to, reason) + `BookingStatusChanged` event after commit; expiry uses `booking.expired` with actor null (D-024).
### Pricing
- `PricingService::quote()` (D-022): base_price_cents → matching active rules in priority asc (ties by id), each on the running price (percent compounds, half-away-from-zero centavo rounding, floor 0) → + add-ons × qty at current price → total; downpayment = round half up(total × downpayment_percent / 100).
- Example Day ₱7,000 on a summer Saturday: +10% weekend → ₱7,700; +₱1,000 summer → ₱8,700; + videoke ₱500 → ₱9,200; downpayment 50% ₱4,600.
- Guest count must be 1..package max_pax; add-ons must be active, qty 1..99. Booking stores totals + add-on unit prices (snapshot).
### Availability
- `AvailabilityService`: a package's window on date D = D@start_time → D@end_time (+1 day when crosses_midnight). Unavailable if any `Booking::active()->overlapping(S, E)` (pending/approved, not soft-deleted) or `BlockedDate::overlapping(S, E)`; half-open, so Day 07–17 and Night 19–05 share a date and a Night ending 05:00 does not block the next Day; 24-Hour conflicts with both.
- Calendar: `unavailableDates()` = two queries + in-memory check per day per active package. Booking window (lead time, max advance) via `isWithinBookingWindow()`.
- Writes are serialized by the advisory lock and backed by the exclusion constraint (D-021).

## Known Issues / TODO
- Resolved in M0.1: local MariaDB was unusable, so the project moved to PostgreSQL (D-014); schema verified with migrate:fresh --seed, rollback and re-migrate on PostgreSQL 18. (M0/M1/M0.1)
- Settings contact/payment/social values are placeholders; replace once the owner answers PLAN.md §14 Q5. (M1)
- Day package hours (7AM–5PM) and Day max pax (50) are proposed defaults pending owner confirmation (PLAN.md §1). (M1)
- Admin nav lists built modules only; add Bookings/Reports entries to `$nav` in layouts/admin.blade.php as they ship (M6/M7). (M0/M2)
- Booking page JS (calendar, quote, steps), gallery lightbox and copy button were not run in a real browser in M5 (Chrome extension unavailable); server endpoints/markup are tested. Manual check recommended. (M5)
- PDF proofs are stored as uploaded (metadata not stripped). (M5)
- Settings content/house rules/cancellation policy are placeholders until the owner provides them (§14). (M5)
- No self-service "forgot password" email yet; owner resets passwords (D-015). Revisit in M8. (M2)
- Dashboard charts/occupancy deferred to M7. (M2)
- Production needs the scheduler cron (`* * * * * php artisan schedule:run`) for booking expiry (D-024). (M4)
- Exclusion constraint does not cover blocked dates; only the service check does. (M4)
- Pricing-rule form preview (Alpine) mirrors `applyRule()` for one rule; keep both in sync if the formula changes. (M4)
- Remove `/design-preview` route + view in M9 (D-007). (M0)
- Drag-and-drop/icon picker JS verified by markup + endpoint tests only (no browser test run in M3); check manually in Chrome. (M3)
- Original uploads keep EXIF (possibly GPS) and are publicly reachable by UUID URL; only resized WebP variants are linked. Consider stripping/not keeping originals in M9. (M3)
