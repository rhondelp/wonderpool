# Milestones

| ID | Phase | Title | Status | Date done | Commit hash |
|---|---|---|---|---|---|
| M0 | 0 | Setup | Done | 2026-10-01 | 7c42f4f |
| M0.1 | 0 | Switch database to PostgreSQL (change) | Done | 2026-10-01 | 6209193 |
| M1 | 1 | Data layer | Done | 2026-10-01 | e1e0b27 |
| M2 | 2 | Admin foundation | Done | 2026-10-01 | e2b4c84 |
| M3 | 3 | Content modules | Done | 2026-10-01 | dd0d451 |
| M4 | 4 | Booking engine | Done | 2026-10-01 | a8ee111 |
| M5 | 5 | Public site | In review | 2026-10-01 | PENDING |
| M6 | 6 | Admin bookings | Not started | | |
| M7 | 7 | Reports & logs | Not started | | |
| M8 | 8 | Notifications | Not started | | |
| M9 | 9 | Hardening | Not started | | |
| M10 | 10 | Deploy & handover | Not started | | |

<!--
REPORT TEMPLATE (one per finished milestone, newest first below this comment)
## M<id> <Title> (Done <date>, commit <hash>)
**Goal:** one line
**Delivered:** bullet list of features
**Files created / modified:** grouped list
**DB changes:** migrations and tables
**Routes:** list
**How to verify:** manual steps + test names
**Key decisions:** reference D-xxx IDs from HISTORY.md
**Edit entry points (where to change things later):** "To change X, edit Y (file:method)"
**Known limitations / follow-ups**
-->

# Reports

## M5 Public site & booking flow (Done 2026-10-01, commit PENDING)
**Goal:** Guests can browse the resort, see real availability, book, upload payment proof and track their booking on mobile and desktop.
**Delivered:**
- Public pages: home (hero, highlights, about, amenities, packages, gallery strip, map, CTA), amenities, packages & rates (+ add-ons), gallery (category filter + accessible lightbox), FAQ, contact, policies; all content from DB/settings (new "Website content" and "SEO" settings), empty sections hidden
- Design: pool/garden gradients (AA-safe 700+ shades under white text), rounded cards, wave dividers, Poppins, mobile-first; lazy images with width/height
- SEO: per-page title/description, canonical, OG/Twitter tags (share image fallback = first gallery photo), sitemap.xml, robots.txt
- Booking: 3 steps on one page (Alpine) with month availability calendar and live quotes from the M4 services; server-validated no-JS fallback; payment step with instructions; private proof upload (images re-encoded, metadata stripped; PDF accepted); success page with copy-able reference code and next steps
- Tracking: reference + mobile (normalized), guest-safe timeline, proof upload/replace while pending; booking pages only for the browser that made/looked up the booking
- Anti-abuse: per-IP rate limits, honeypot, Turnstile hook (off by default)
**Files created / modified:**
- HTTP: app/Http/Controllers/Public/{Page,Booking,TrackBooking}Controller.php, app/Http/Requests/Public/*.php, app/Rules/{PhilippineMobile,Turnstile}.php, routes/web.php, app/Providers/AppServiceProvider.php
- Services/domain: app/Services/Booking/{GuestBookingService,PaymentProofService}.php, app/Services/PublicContentService.php, app/Support/PhoneNumber.php, app/Exceptions/Booking/{BookingWindow,PaymentProofNotAllowed,InvalidPaymentProof}Exception.php, app/Enums/{SettingGroup,ActivityAction}.php, app/Services/Booking/PriceBreakdown.php, config/wonderpool.php, .env.example
- Views/JS: resources/views/public/**, resources/views/components/ui/{wave-divider,section,prose}.blade.php, resources/views/components/public/*.blade.php, resources/views/layouts/public.blade.php, resources/views/partials/head.blade.php, resources/js/{booking,app}.js; removed resources/views/home.blade.php, public/robots.txt
- Tests: tests/Feature/Public/{Pages,BookingFlow,QuoteEndpoint,TrackBooking,AntiAbuse}Test.php, tests/Unit/PhoneNumberTest.php
- Docs: CHANGELOG.md, HISTORY.md, MILESTONES.md, docs/{admin-guide,deployment}.md
**DB changes:** none (new settings keys seeded by SettingSeeder).
**Routes:** home, amenities, packages, gallery, faq, contact, policies, sitemap, robots, book, book.availability, book.quote, book.store, book.payment, book.payment.store, book.done, track, track.lookup, track.show (HISTORY Routes Table).
**How to verify:** `php artisan db:seed --class=SettingSeeder` → `npm run build` → `php artisan serve` → browse all pages at phone and desktop width; Book → pick a package, see booked days greyed in the calendar, pick a free date (price appears), fill details, add an add-on (price updates), confirm → upload a JPG or PDF → success page shows the code → `/track` with the code in lowercase and `0917…` phone → timeline. Automated: `vendor/bin/pest` (425 tests; tests/Feature/Public/*), `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` (no errors).
**Key decisions:** D-025 (normalized code/phone + session access), D-026 (private proofs, re-encode, PDF as-is), D-027 (content/SEO settings, safe Markdown-lite), D-028 (rate limits, honeypot, Turnstile flag).
**Edit entry points (where to change things later):**
- **Booking steps** (order, what each step shows, step labels): `resources/views/public/book/index.blade.php` (one `<fieldset>` per step, step indicator list at the top) and `resources/js/booking.js` (`go()`, `canContinue`, calendar/quote fetching). Server side of the flow: `app/Http/Controllers/Public/BookingController.php` + `app/Services/Booking/GuestBookingService.php`.
- **Booking form fields:** add the input to the right fieldset in `resources/views/public/book/index.blade.php`, its rule/message in `app/Http/Requests/Public/StoreBookingRequest.php` (`rules()`, `messages()`, `bookingData()`), and if it must be stored, the column (migration + `Booking::$fillable`) and `BookingService::create()`. Quote-affecting fields also go in `QuoteRequest` and `GuestBookingService::quote()`.
- **Page sections:** each page is `resources/views/public/{home,amenities,packages,gallery,faq,contact,policies}.blade.php` (home sections are commented blocks using `x-ui.section :when`); their data comes from `app/Http/Controllers/Public/PageController.php` → `app/Services/PublicContentService.php`. Header/footer: `resources/views/layouts/public.blade.php` (`$nav`).
- **Page text:** Admin → Settings → Website content / SEO (keys in `app/Enums/SettingGroup.php`).
- **Payment instructions:** Admin → Settings → Payment (`payment.instructions`); shown in booking step 3, the payment page and the tracking page.
- Proof rules/storage: `app/Http/Requests/Public/UploadPaymentProofRequest.php` (types/size), `app/Services/Booking/PaymentProofService.php` (disk, folder, MAX_EDGE, QUALITY).
- Rate limits: `AppServiceProvider::boot()` (`RateLimiter::for(...)`); Turnstile: `.env` TURNSTILE_* + `app/Rules/Turnstile.php`.
- Timeline wording: `GuestBookingService::timeline()` / `statusEntry()`.
**Known limitations / follow-ups:**
- The booking page JavaScript (calendar clicks, live quote, step switching), gallery lightbox and copy button were NOT exercised in a real browser (Chrome extension unavailable); endpoints and markup are covered by tests. Please click through once.
- PDF proofs keep their metadata (cannot re-encode with GD).
- Content/policy settings hold placeholders until the owner supplies real text (PLAN.md §14).
- Admin proof viewing and approval UI: M6. Email notifications: M8.

## M4 Booking engine (Done 2026-10-01, commit a8ee111)
**Goal:** Availability, pricing, safe booking creation, status lifecycle, pending-hold expiry, and admin management of blocked dates and pricing rules. No public UI.
**Delivered:**
- `AvailabilityService`: package windows (Night/24-Hour cross midnight), half-open checks vs pending/approved bookings and blocked dates, conflict lookup, per-day calendar map (2 queries), lead-time/max-advance check
- `PricingService` + `PriceBreakdown`: matching rules stacked by priority on the running price (owner's choice), add-ons, max_pax, integer-centavo rounding, downpayment from settings
- `ReferenceCodeGenerator`: `WP-YYMM-XXXX`, unambiguous alphabet, unique incl. soft-deleted
- `BookingService`: create (pg_advisory_xact_lock → re-check → snapshot → pending), transition (central map, reject reason, approval stamp, completion only after the stay, `BookingStatusChanged` event, audit), reschedule (re-check ignoring itself, optional package switch and reprice), expireStale
- PostgreSQL exclusion constraint `bookings_no_overlap` (second line of defense; 23P01 → SlotUnavailableException)
- `bookings:expire-stale` every 15 minutes (`--dry-run` supported)
- Admin Blocked dates (whole days or exact range, overlap warning, upcoming/past filter) and Pricing rules (validation per type, live sample preview, quote checker showing stacked rules)
**Files created / modified:**
- Services: app/Services/Booking/{AvailabilityService,PricingService,PriceBreakdown,ReferenceCodeGenerator,BookingService,BlockedDateService}.php
- Domain: app/Exceptions/Booking/*.php, app/Events/BookingStatusChanged.php, app/Enums/ActivityAction.php, app/Models/{BlockedDate,PricingRule}.php
- Console: app/Console/Commands/ExpireStaleBookings.php, routes/console.php
- DB: database/migrations/2026_10_01_001500_add_booking_overlap_exclusion_constraint.php, database/factories/BookingFactory.php
- HTTP/UI: app/Http/Controllers/Admin/{BlockedDate,PricingRule}Controller.php, app/Http/Requests/Admin/Content/*{BlockedDate,PricingRule}Request.php, app/Policies/{BlockedDate,PricingRule}Policy.php, resources/views/admin/{blocked-dates,pricing-rules}/*, resources/views/layouts/admin.blade.php, routes/web.php
- Tests: tests/Feature/Booking/*.php, tests/Feature/Admin/Booking/*.php, tests/Pest.php
- Docs: CHANGELOG.md, HISTORY.md, MILESTONES.md, docs/{deployment,admin-guide}.md
**DB changes:** exclusion constraint `bookings_no_overlap` on bookings (reversible; verified migrate → rollback → migrate on PostgreSQL 18).
**Routes:** admin.blocked-dates.*, admin.pricing-rules.* (resources except show).
**How to verify:** `php artisan migrate`; `vendor/bin/pest` (335 tests; tests/Feature/Booking/* incl. "serializes creation with a PostgreSQL advisory lock", "has a database exclusion constraint…", "enforces the transition map for every status pair"); `vendor/bin/pint --test`; `vendor/bin/phpstan analyse` (no errors); `php artisan schedule:list` (bookings:expire-stale */15); `php artisan bookings:expire-stale --dry-run`; tinker: `app(App\Services\Booking\PricingService::class)->quote(App\Models\Package::first(), now()->next('Saturday'))->toArray()` and `app(App\Services\Booking\BookingService::class)->create([...])` (smoke-tested in a rolled-back transaction). Admin: Pricing rules → add a weekend +10% rule, watch the sample, use Quote check on a Saturday; Blocked dates → block a day that has a booking and read the warning.
**Key decisions:** D-021 (advisory lock + exclusion constraint), D-022 (pricing stacking/rounding), D-023 (reference codes), D-024 (expiry), D-009 (half-open windows).
**Edit entry points (where to change things later):**
- **Transition map:** `app/Services/Booking/BookingService.php` → `TRANSITIONS` constant; extra preconditions (reason required, completion timing, approval stamp) in `applyTransition()`.
- **Hold duration:** Settings → Booking rules "Pending hold (hours)" (key `booking.pending_hold_hours`); default and limits in `app/Enums/SettingGroup.php::fields()`; expiry logic in `BookingService::expireStale()`; schedule frequency in `routes/console.php`.
- **Reference code format:** `app/Services/Booking/ReferenceCodeGenerator.php` constants `PREFIX`, `ALPHABET`, `RANDOM_LENGTH`, `MAX_ATTEMPTS` and the `ym` part in `generate()` (column `reference_code` is varchar(20)).
- **Pricing rule precedence:** order in `PricingService::matchingRules()` (`orderBy('priority')->orderBy('id')`); how a rule changes the price in `applyRule()` / `percentOf()`; stacking loop in `quote()`. Keep the Alpine preview in `resources/views/admin/pricing-rules/_form.blade.php` in sync with `applyRule()`.
- Downpayment rounding: `PricingService::quote()` (`downpaymentRequiredCents`).
- Availability rules (what blocks a window): `AvailabilityService::isAvailable()` / `bookingsQuery()`; window shapes: `resolveWindow()`.
- Lock key: `BookingService::BOOKING_LOCK_KEY`; constraint: the M4 migration.
**Known limitations / follow-ups:**
- Production must run the scheduler cron for expiry.
- The exclusion constraint covers bookings only; blocked dates rely on the service check.
- `BookingStatusChanged` has no listeners until M8 (notifications). Public booking UI/quote endpoint in M5; admin booking actions UI in M6.
- Plan deviation: no retry on reference-code unique violations; creation is serialized by the advisory lock, so the existence check cannot race (unique index still guards).

## M3 Content modules (Done 2026-10-01, commit dd0d451)
**Goal:** Owner can fully manage packages, add-ons, amenities, gallery and FAQs from the admin UI, with validation, audit and ordering.
**Delivered:**
- Five owner-only modules (controller, Store/Update Form Requests, policy, views, routes, nav entry); staff get 403
- Index pages: case-insensitive ILIKE search (wildcards escaped), active/inactive (gallery: visible/hidden, + category) filter, pagination, empty states, success/warning flashes, confirm-delete modal
- Packages: pesos → centavos, HH:MM times checked against "Ends the next day" (same-day end > start; overnight end ≤ start), unique uppercase code; delete refused when any booking (incl. soft-deleted) uses it → "deactivate instead" warning
- Add-ons: same guard when used on bookings (FK restrict would otherwise error)
- Amenities: searchable curated heroicon picker, optional photo (replace/remove)
- Gallery: multi-upload (≤20 × 5 MB jpg/png/webp), category/caption/visibility, quick show/hide, original + WebP large/thumb versions, files removed on delete
- FAQs: CRUD + order
- One reusable sortable component (Alpine `sortable` + x-admin.sortable/sort-handle, drag-and-drop and keyboard up/down) used by packages, amenities, gallery and FAQs; PATCH reorder endpoints with a subset-safe renumbering algorithm
- Every create/update/delete/reorder logged via ActivityLogger (content.* actions)
**Files created / modified:**
- Services/domain: app/Services/Content/{ContentService,ImageService,AmenityService,GalleryService}.php, app/Models/Concerns/AdminListable.php, app/Models/Contracts/GuardsDeletion.php, app/Exceptions/ContentInUseException.php, app/Enums/{ImageVariant,ActivityAction}.php, app/Support/AmenityIcons.php, app/Models/{Package,AddOn,Amenity,GalleryImage,Faq}.php, app/Providers/AppServiceProvider.php
- HTTP: app/Http/Controllers/Admin/{Package,AddOn,Amenity,Gallery,Faq}Controller.php, app/Http/Requests/Admin/Content/*.php, app/Policies/{Content,Package,AddOn,Amenity,GalleryImage,Faq}Policy.php, routes/web.php
- UI: resources/js/{sortable,app}.js, resources/views/admin/{packages,add-ons,amenities,gallery,faqs}/*, resources/views/components/admin/{sortable,sort-handle,confirm-delete,index-filters,icon-picker}.blade.php, resources/views/components/ui/{checkbox,file-input,button}.blade.php, resources/views/layouts/admin.blade.php
- Tests: tests/Feature/Admin/Content/{Packages,AddOns,Amenities,Gallery,Faqs,ContentAccess}Test.php, tests/Feature/Services/ContentServiceTest.php
- Build/docs: composer.json, composer.lock (intervention/image 3.11), .github/workflows/ci.yml (gd), README.md, docs/{deployment,admin-guide}.md, CHANGELOG.md, HISTORY.md, MILESTONES.md
**DB changes:** none (M1 tables used as-is).
**Routes:** resources admin.{packages,add-ons,amenities,gallery,faqs}.* (except show); PATCH admin.{packages,amenities,gallery,faqs}.reorder; PATCH admin.gallery.toggle-visibility.
**How to verify:** enable `extension=gd` in php.ini → `php artisan storage:link` → sign in as owner → Packages/Add-ons/Amenities/Gallery/FAQs in the sidebar: add, edit, search (try lowercase), filter, drag rows (status line says "Order saved."), delete (package with a booking shows the deactivate warning); upload several gallery images and check thumbnails. Automated: `vendor/bin/pest` (212 tests; tests/Feature/Admin/Content/*, ContentServiceTest), `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` (no errors), `npm run build`.
**Key decisions:** D-018 (image storage paths/variants), D-019 (ContentService + delete guards + audit), D-020 (ordering + search/filter), D-016 (owner-only).
**Edit entry points (where to change things later):**
- Add a field to **Packages**: migration (new `add_*_to_packages_table`), `app/Models/Package.php` ($fillable, casts, @property), `app/Http/Requests/Admin/Content/PackageRequest.php` (rules(); payload() if it needs conversion), `resources/views/admin/packages/_form.blade.php`; optionally the column in `resources/views/admin/packages/index.blade.php` and `adminSearchColumns()`.
- Add a field to **Add-ons**: migration, `app/Models/AddOn.php`, `app/Http/Requests/Admin/Content/AddOnRequest.php`, `resources/views/admin/add-ons/_form.blade.php` (+ `index.blade.php`).
- Add a field to **Amenities**: migration, `app/Models/Amenity.php`, `app/Http/Requests/Admin/Content/AmenityRequest.php` (exclude non-column inputs in payload()), `resources/views/admin/amenities/_form.blade.php` (+ `index.blade.php`). New icon choices: `app/Support/AmenityIcons.php`.
- Add a field to **Gallery**: migration, `app/Models/GalleryImage.php`, `StoreGalleryImagesRequest.php` + `UpdateGalleryImageRequest.php` (rules), `app/Services/Content/GalleryService.php::upload()` (batch fields), `resources/views/admin/gallery/{create,edit}.blade.php`. New category: `app/Enums/GalleryCategory.php`.
- Add a field to **FAQs**: migration, `app/Models/Faq.php`, `app/Http/Requests/Admin/Content/FaqRequest.php`, `resources/views/admin/faqs/_form.blade.php` (+ `index.blade.php`).
- Image sizes/quality/paths: `app/Enums/ImageVariant.php` (maxEdge), `app/Services/Content/ImageService.php` (QUALITY, layout). Upload limits: `app/Http/Requests/Admin/Content/ImageRules.php`, `StoreGalleryImagesRequest::MAX_FILES`.
- Delete guards: `deletionBlockedReason()` in `app/Models/Package.php` / `app/Models/AddOn.php`; implement `GuardsDeletion` on other models to add one.
- Make another module sortable: wrap its list in `<x-admin.sortable :url>`, add `data-sortable-id` + `draggable="true"` + `<x-admin.sort-handle>` per item, add a PATCH `reorder` route (before the resource) calling `ContentService::reorder()`.
- Search columns / filter column: `adminSearchColumns()` / `adminStateColumn()` on the model.
**Known limitations / follow-ups:**
- JS behaviour (drag-and-drop, keyboard move, icon search) was not exercised in a browser in this phase (Chrome extension unavailable); server endpoints and markup are tested. Manual check recommended.
- Public originals keep EXIF metadata; consider stripping or not keeping originals (M9).
- Public pages that display this content come in M5; pricing rules and blocked dates in M4.

## M2 Admin foundation (Done 2026-10-01, commit e2b4c84)
**Goal:** Secure admin panel entry (auth, roles), dashboard shell, and owner-editable settings via a cached SettingService.
**Delivered:**
- `/admin/login` sign-in/out: 5 failed attempts/min lockout per email+IP, disabled accounts rejected, session regeneration, `last_login_at`, login/logout audited
- Forced password change (`must_change_password`): seeded owner on first login and any owner-reset temporary password; voluntary change from the account menu
- Middleware `active` (signs out disabled users mid-session), `password.changed`, `role:owner`; gates manage-settings / manage-users / view-financials; UserPolicy
- Owner-only Users: list, add (temporary password), edit, enable/disable, reset password; no self-disable/demote/reset
- Owner-only Settings: 5 tabs from `SettingGroup` registry, validation from the registry, changes audited with from/to values
- Dashboard shell: pending, arrivals today, next 7 days, revenue this month (owner only), next 5 upcoming bookings
- ActivityLogger + ActivityAction enum (activity log viewer comes in M7)
- Role-aware admin nav (only built modules) and account menu
**Files created / modified:**
- Enums/services: app/Enums/{ActivityAction,SettingGroup}.php, app/Services/{ActivityLogger,SettingService,UserService,DashboardService}.php, app/Providers/AppServiceProvider.php
- HTTP: app/Http/Controllers/Admin/{DashboardController,SettingController,UserController}.php, app/Http/Controllers/Admin/Auth/{LoginController,PasswordController}.php, app/Http/Middleware/*.php, app/Http/Requests/Admin/*.php, app/Policies/UserPolicy.php, bootstrap/app.php, routes/web.php
- Data: database/migrations/2026_10_01_001400_add_security_columns_to_users_table.php, app/Models/User.php, database/factories/UserFactory.php, database/seeders/{OwnerSeeder,SettingSeeder}.php
- Views: resources/views/layouts/{admin,auth}.blade.php, resources/views/partials/admin-sidebar.blade.php, resources/views/admin/**, resources/views/components/ui/{input,select,textarea}.blade.php
- Tests: tests/Feature/Admin/{AuthTest,AccessControlTest,UsersTest,SettingsTest,DashboardTest}.php, tests/Feature/Services/SettingServiceTest.php
- Docs: CHANGELOG.md, HISTORY.md, MILESTONES.md, docs/admin-guide.md
**DB changes:** users.must_change_password (bool, default false), users.last_login_at (timestamp, nullable).
**Routes:** admin.login(.store), admin.logout, admin.password.edit/update, admin.dashboard, admin.settings.index/edit/update, admin.users.index/create/store/edit/update, admin.users.toggle-active, admin.users.reset-password (table in HISTORY.md).
**How to verify:** `php artisan migrate` (or `migrate:fresh --seed`) → `php artisan serve` → `/admin` redirects to `/admin/login` → sign in with OWNER_EMAIL/OWNER_PASSWORD → forced to `/admin/password` → set new password → dashboard → Settings (edit Booking rules) → Users (add staff, disable/enable, reset password) → sign in as staff: no Users/Settings links, `/admin/settings/general` = 403. Automated: `vendor/bin/pest` (116 tests; tests/Feature/Admin/*, SettingServiceTest), `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` (level 6, no errors).
**Key decisions:** D-015 (admin auth + temporary passwords), D-016 (role enforcement), D-017 (settings registry + cache).
**Edit entry points (where to change things later):**
- To add/change a setting: `app/Enums/SettingGroup.php` (`fields()`), then HISTORY.md Settings Keys; read it with `SettingService::get/int`.
- To add an admin nav item: `$nav` in `resources/views/layouts/admin.blade.php` (route, active pattern, gate).
- To change who may do what: gates in `app/Providers/AppServiceProvider.php`, `app/Policies/UserPolicy.php`, route groups in `routes/web.php`.
- To change login throttling: `app/Http/Requests/Admin/LoginRequest.php` (`MAX_ATTEMPTS`, `throttleKey()`).
- To change password strength: `Password::min(8)->letters()->numbers()` in UpdateOwnPasswordRequest, StoreUserRequest, ResetUserPasswordRequest.
- To log a new action: add a case to `app/Enums/ActivityAction.php` and call `ActivityLogger::log()`.
- To change dashboard figures: `app/Services/DashboardService.php`.
**Known limitations / follow-ups:**
- No email "forgot password" flow (owner resets instead); revisit with mail in M8.
- Activity log viewer, dashboard charts/occupancy: M7. Bookings/content nav entries added in M3/M6.
- Settings for logo, cancellation policy and notification toggles are added in the phases that use them.

## M0.1 Switch database to PostgreSQL (Done 2026-10-01, commit 6209193)
**Goal:** Replace MySQL with PostgreSQL (MySQL does not run on the dev machine). Infrastructure only: no business rules, features or UI change.
**Delivered:**
- PostgreSQL as default connection (`pgsql`), `.env.example` placeholders, tests on `wonderpool_test`, CI with a `postgres:16` service
- `json` → `jsonb` (pricing_rules.days_of_week, activity_logs.properties); no MySQL enums/engine/charset/raw SQL existed
- Emails stored trimmed + lowercase (User `email`, Booking `guest_email` mutators; OwnerSeeder normalizes the lookup key)
- PLAN.md §5.2: booking creation serialized with `pg_advisory_xact_lock` + optional partial exclusion constraint
- CLAUDE.md database rules; docs/README updated
**Files created / modified:**
- Config/CI: config/database.php, phpunit.xml, .env.example, .github/workflows/ci.yml, .gitignore
- Code: database/migrations/2026_10_01_000400_create_pricing_rules_table.php, database/migrations/2026_10_01_001300_create_activity_logs_table.php, app/Models/User.php, app/Models/Booking.php, database/seeders/OwnerSeeder.php
- Tests: tests/Feature/Models/PostgresCompatibilityTest.php (new), tests/Pest.php
- Docs: PLAN.md, CLAUDE.md, HISTORY.md, CHANGELOG.md, MILESTONES.md, README.md, docs/{database,deployment,architecture}.md
**DB changes:** two columns json → jsonb (existing migrations edited; nothing deployed yet).
**Routes:** none
**How to verify:** create role/databases (README) → set DB_* in .env → `php artisan migrate:fresh --seed`; `vendor/bin/pest` (62 tests incl. PostgresCompatibilityTest, BookingScopesTest "does not treat touching boundaries as overlaps"); `vendor/bin/pint --test`; `vendor/bin/phpstan analyse`. Verified locally on PostgreSQL 18: migrate:fresh --seed, rollback, re-migrate, all green.
**Key decisions:** D-014 (PostgreSQL), D-006 (tests on PostgreSQL), D-010 (string enums).
**Edit entry points (where to change things later):**
- To change DB connection defaults, edit `config/database.php` (`default`, `connections.pgsql`) and `.env.example`.
- To change the test database, edit `phpunit.xml` (`DB_DATABASE`) and `.github/workflows/ci.yml` (postgres service + Pest env).
- To change email normalization, edit `app/Models/User.php` (`email()`) and `app/Models/Booking.php` (`guestEmail()`).
**Known limitations / follow-ups:**
- Advisory lock + optional exclusion constraint are implemented in M4 (BookingService), not here.
- jsonb does not keep object key order; compare decoded arrays loosely in tests.

## M1 Data layer (Done 2026-10-01, commit e1e0b27)
**Goal:** Full schema, models, enums, factories and seeders per PLAN.md §4, ready for services.
**Delivered:**
- 7 string-backed enums with label()/color(); Money helper (centavos, D-001)
- 13 migrations (users alter + 12 tables) with explicit FK delete rules and indexes; reversible
- 13 models with casts, relationships, PHPDoc @property blocks, scopes (`Booking::active()`, `Booking::overlapping()`, etc.) and ₱ accessors
- Factories for every model with PH names and +639 mobiles; booking states (approved/rejected/cancelled/completed/forPackageOn/window)
- Seeders: owner from .env, 3 PLAN packages, 8 amenities, 12 settings, 6 FAQs (all idempotent)
- docs/database.md ERD (Mermaid) + table dictionary
- Theme palette and badge statuses aligned with PLAN.md §7 (AA-safe primary)
**Files created / modified:**
- Enums/helpers: app/Enums/*.php, app/Support/Money.php, config/wonderpool.php
- Models: app/Models/{User,Package,AddOn,BookingAddOn,PricingRule,Booking,Payment,BlockedDate,Amenity,GalleryImage,Faq,Setting,ActivityLog}.php
- DB: database/migrations/2026_10_01_*.php, database/factories/*.php, database/factories/Concerns/PhilippineData.php, database/seeders/*.php
- Tests: tests/Feature/Models/{FactoriesTest,BookingScopesTest}.php, tests/Feature/SeederTest.php, tests/Unit/{MoneyTest,EnumsTest}.php, tests/Pest.php
- UI alignment: resources/css/app.css, components/ui/{button,badge}.blade.php, partials/head.blade.php, home.blade.php, layouts/public.blade.php, design-preview.blade.php
- Docs/env: docs/database.md, .env.example
**DB changes:** users (+role, +is_active); new packages, add_ons, pricing_rules, bookings, booking_add_ons, payments, blocked_dates, amenities, gallery_images, faqs, settings, activity_logs.
**Routes:** none
**How to verify:** set OWNER_EMAIL/OWNER_PASSWORD in .env → `php artisan migrate:fresh --seed`; `vendor/bin/pest` (57 tests: FactoriesTest, BookingScopesTest incl. "does not treat touching boundaries as overlaps", SeederTest, MoneyTest, EnumsTest); `vendor/bin/phpstan analyse`; /design-preview shows enum badges. Verified on PostgreSQL 18 (after M0.1): migrate:fresh --seed, full rollback, re-migrate.
**Key decisions:** D-001 (updated), D-003, D-004 (finalized), D-008 (AA 700 shades), D-009 (local time, half-open windows), D-010 (string enums), D-011 (FK delete rules), D-012 (settings/owner env), D-013 (E.164 phones).
**Edit entry points (where to change things later):**
- To change what counts as "occupying" the resort, edit `app/Enums/BookingStatus.php` (`blocking()`).
- To change overlap semantics, edit `app/Models/Booking.php` (`scopeOverlapping`) and `app/Models/BlockedDate.php` (`scopeOverlapping`).
- To change status labels/badge colors, edit `app/Enums/*Status.php` (`label()`, `color()`).
- To change money formatting, edit `app/Support/Money.php` (`format`).
- To change default packages/settings/FAQs/amenities, edit `database/seeders/{Package,Setting,Faq,Amenity}Seeder.php`.
- To change sample data, edit `database/factories/Concerns/PhilippineData.php`.
**Known limitations / follow-ups:**
- Local DB: resolved by M0.1 (PostgreSQL); schema now verified on PostgreSQL 18.
- Contact/payment/social settings and Day package hours/pax are placeholders pending owner answers (PLAN.md §14).
- Transition rules, availability service, pricing and reference-code generator are M4.

## M0 Setup (Done 2026-10-01, commit 7c42f4f)
**Goal:** Laravel project, tooling, design tokens and base layouts ready for feature work.
**Delivered:**
- Laravel 12 in repo root; database env (db `wonderpool`; MySQL at M0, PostgreSQL since M0.1), APP_NAME, APP_TIMEZONE=Asia/Manila
- Tailwind v4 theme: `pool-*` / `garden-*` palettes, Poppins 400–700 (swap), forms plugin; Alpine + focus
- Layouts `layouts.public` (mobile menu) and `layouts.admin` (desktop sidebar, mobile drawer), shared meta head, flash messages
- Components: x-ui.button/input/select/textarea/card/badge/modal/alert/flash/empty-state, x-admin.stat-card/page-header
- `/design-preview` (local only) gallery of every component
- Pint (psr12), Larastan level 6, Pest smoke tests, GitHub Actions CI; README + docs stubs
**Files created / modified:**
- Config/tooling: .env.example, config/app.php, .gitignore, pint.json, phpstan.neon, .github/workflows/ci.yml, composer.json, package.json
- Front-end: resources/css/app.css, resources/js/app.js
- Views: resources/views/layouts/{public,admin}.blade.php, partials/{head,admin-sidebar}.blade.php, components/ui/*.blade.php, components/admin/*.blade.php, home.blade.php, design-preview.blade.php
- Routes: routes/web.php
- Tests: tests/Pest.php, tests/TestCase.php, tests/Feature/SmokeTest.php
- Docs: README.md, docs/*.md
**DB changes:** none beyond Laravel defaults (users, cache, jobs); first migrated on PostgreSQL in M0.1.
**Routes:** GET / (home), GET /design-preview (local only), GET /up
**How to verify:** `npm run build`; `php artisan serve` → open /design-preview; `vendor/bin/pint --test`; `vendor/bin/phpstan analyse`; `vendor/bin/pest` (SmokeTest: renders home, hides design preview outside local).
**Key decisions:** D-001 (money = integer centavos), D-002 (Tailwind v4 @theme), D-003/D-004 (provisional palette & badge colors), D-005 (layouts), D-006 (tests; SQLite at M0, PostgreSQL `wonderpool_test` since M0.1), D-007 (design preview local only).
**Edit entry points (where to change things later):**
- To change colors/fonts, edit `resources/css/app.css` (`@theme` block).
- To change status badge colors, edit `resources/views/components/ui/badge.blade.php` (`$statusColors`).
- To change admin sidebar items, edit `resources/views/layouts/admin.blade.php` (`$nav`).
- To change public nav/footer, edit `resources/views/layouts/public.blade.php`.
- To change meta tags/SEO defaults, edit `resources/views/partials/head.blade.php`.
**Known limitations / follow-ups:**
- PLAN.md was empty during M0 (not committed) → palette/status colors provisional; reconcile.
- Local MariaDB was unusable; resolved in M0.1 by switching to PostgreSQL.
- Nav links are placeholders; `/design-preview` must be removed in M9.
