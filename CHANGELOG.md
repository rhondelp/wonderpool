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

## [change/developer-credits] Developer credits - 2026-10-03
### Added
- Developer credit (name + email, no phone: public repo) in the public footer and admin sidebar, from `config('wonderpool.developer')`; README "Developer" section; composer.json authors (files: config/wonderpool.php, resources/views/layouts/public.blade.php, resources/views/partials/admin-sidebar.blade.php, README.md, composer.json, tests/Feature/DeveloperCreditTest.php)
### DB: none
### Routes: none

## [M8] Email notifications & reminders - 2026-10-03
### Added
- Queued notifications BookingReceived (guest + owners), BookingApproved, BookingRejected (reason), BookingCancelled (incl. expiry), PaymentProofReceived (owners), BookingReminder, TestEmail; base BookingNotification (afterCommit, 3 tries, failure -> activity log) (files: app/Notifications/*.php)
- NotificationChannelResolver: channels per type from config, dropped when the recipient has no route (SMS-ready) (files: app/Notifications/Channels/NotificationChannelResolver.php, config/wonderpool.php)
- NotificationService (recipients, switches, reminders, test email) and listener SendBookingNotifications (after commit) (files: app/Services/NotificationService.php, app/Listeners/SendBookingNotifications.php)
- Events BookingCreated and PaymentProofUploaded (files: app/Events/{BookingCreated,PaymentProofUploaded}.php)
- Command bookings:send-reminders (daily 09:00 Asia/Manila, idempotent via reminded_at) (files: app/Console/Commands/SendBookingReminders.php, routes/console.php)
- Enum NotificationType; settings group Notifications (7 switches + reminder days) and general.logo_url; checkbox settings; SettingService::bool() (files: app/Enums/{NotificationType,SettingGroup}.php, app/Services/SettingService.php, resources/views/admin/settings/edit.blade.php)
- "Send test email" button + route; ActivityAction mail.test_queued, mail.failed (files: app/Http/Controllers/Admin/TestEmailController.php, routes/web.php, app/Enums/ActivityAction.php)
- Branded Markdown mail: components + wonderpool theme, booking templates, brand view composer (files: resources/views/mail/**, config/mail.php, app/Providers/AppServiceProvider.php)
- Tests: every event + switch-off, rollback, queueing, content, failure log, reminder idempotency, schedule, settings, test email, resolver (files: tests/Feature/Notifications/*.php)
### Changed
- BookingService::create fires BookingCreated; reschedule clears reminded_at; PaymentProofService fires PaymentProofUploaded (files: app/Services/Booking/{BookingService,PaymentProofService}.php, app/Events/BookingStatusChanged.php)
- Docs: notifications + adding a channel, queue worker/Supervisor, admin guide emails; .env.example mail notes + REMINDER_TIME (files: docs/{architecture,deployment,admin-guide}.md, .env.example)
### DB: added 2026_10_03_000100_add_reminded_at_to_bookings_table (reversible)
### Routes: added admin.settings.test-email (POST /admin/settings/notifications/test-email)
### Breaking / Notes
- Run `php artisan migrate` and `php artisan db:seed --class=SettingSeeder`. A queue worker must run (`php artisan queue:work`) or no email is sent.

## [M7] Reports & activity log - 2026-10-03
### Added
- ReportService: summary (revenue received, booked value, stays, occupancy, status counts), gap-filled daily/monthly series, per-package breakdown, CSV booking rows, 30-day occupancy strip (files: app/Services/ReportService.php)
- ReportExportService: bookings CSV (UTF-8 BOM, plain peso decimals, formula-injection guard), PDF data, audit-logged exports (files: app/Services/ReportExportService.php)
- Reports page with presets, filters, charts, status and package tables; CSV and PDF exports, owner only (files: app/Http/Controllers/Admin/ReportController.php, app/Http/Requests/Admin/ReportRequest.php, resources/views/admin/reports/{index,pdf}.blade.php)
- Activity log screen: filters by person, area, action, dates; ILIKE search on booking reference and details; subject links (files: app/Services/ActivityLogService.php, app/Http/Controllers/Admin/ActivityLogController.php, app/Http/Requests/Admin/ActivityLogRequest.php, resources/views/admin/activity-log/index.blade.php)
- Components x-admin.bar-chart (server-rendered SVG + data table) and x-admin.occupancy-strip (files: resources/views/components/admin/{bar-chart,occupancy-strip}.blade.php)
- ActivityAction report.exported + modules(); Money::decimal(), Money::compact(); gate view-activity-log (files: app/Enums/ActivityAction.php, app/Support/Money.php, app/Providers/AppServiceProvider.php)
- Tests: report figures, pages, exports, access, activity log filters, money helpers (files: tests/Feature/Services/ReportServiceTest.php, tests/Feature/Admin/Reports/{ReportsTest,ActivityLogTest}.php, tests/Unit/MoneyTest.php)
### Changed
- Dashboard shows six-month revenue (owner) and confirmed-stay charts plus the next-30-days strip (files: app/Http/Controllers/Admin/DashboardController.php, resources/views/admin/dashboard.blade.php)
- Admin nav: Reports and Activity log entries; routes (files: resources/views/layouts/admin.blade.php, routes/web.php)
- Admin guide: reports & activity log (files: docs/admin-guide.md)
### DB: none
### Routes: added admin.reports.index, admin.reports.csv, admin.reports.pdf, admin.activity-log
### Breaking / Notes
- None. No new dependencies (charts are SVG; PDF reuses DomPDF).

## [M6] Admin bookings & payments - 2026-10-01
### Added
- Migration: bookings source, created_by, original_total_cents, price_override_reason, cancellation_reason; payments notes, recorded_by, rejection_reason (files: database/migrations/2026_10_01_001600_add_admin_fields_to_bookings_and_payments.php)
- Enums BookingSource, PaymentState; ActivityAction booking.price_overridden/notes_updated, payment.recorded/verified/rejected (files: app/Enums/{BookingSource,PaymentState,ActivityAction}.php)
- PaymentService (record/verify/reject/void/summary) and BookingAdminService (approve with downpayment rule, reject, cancel, complete, reschedule, notes, walk-ins, timeline, counts, listing) (files: app/Services/Booking/{PaymentService,BookingAdminService}.php)
- Exceptions DownpaymentNotCovered, PaymentActionNotAllowed, PriceOverrideNotAllowed (files: app/Exceptions/Booking/*.php)
- Admin bookings index (tabs, filters, ILIKE search, sorting, payment state), detail page (snapshot, payments, proof preview modal, timeline, notes, action modals), walk-in form with owner price override, receipt view + PDF (files: app/Http/Controllers/Admin/{BookingController,BookingActionController,PaymentController}.php, app/Http/Requests/Admin/Booking/*.php, resources/views/admin/bookings/*.blade.php, resources/views/layouts/print.blade.php)
- BookingPolicy, PaymentPolicy, gate manage-bookings, nav entry (files: app/Policies/{BookingPolicy,PaymentPolicy}.php, app/Providers/AppServiceProvider.php, resources/views/layouts/admin.blade.php, routes/web.php)
- Shared calendar/quote partials (files: resources/views/partials/{availability-calendar,quote-panel}.blade.php)
- Tests: index, actions, payments + proof access, walk-ins, override, receipts (files: tests/Feature/Admin/Bookings/*.php)
- Dependency barryvdh/laravel-dompdf 3.1 (files: composer.json, composer.lock)
### Changed
- BookingService::create supports source/created_by/owner price override; transition stores cancellation_reason (files: app/Services/Booking/BookingService.php)
- AvailabilityService::unavailableDates and GuestBookingService::calendar/quote gain admin mode (ignore booking, no lead time); guest timeline shows payment confirmed / not accepted (files: app/Services/Booking/{AvailabilityService,GuestBookingService}.php)
- Booking model scopes search/startingBetween/paymentState; Payment fields + helpers; ActivityLog user typed nullable (files: app/Models/{Booking,Payment,ActivityLog}.php)
- Public booking page uses the shared partials; booking.js accepts `extra` params; dashboard rows link to bookings (files: resources/views/public/book/index.blade.php, resources/js/booking.js, resources/views/admin/dashboard.blade.php)
- Admin guide: managing bookings (files: docs/admin-guide.md)
### DB: added 2026_10_01_001600_add_admin_fields_to_bookings_and_payments (reversible)
### Routes: added admin.bookings.{index,create,store,calendar,quote,show,receipt,approve,reject,cancel,complete,reschedule,notes,payments.store}, admin.payments.{verify,reject,proof}
### Breaking / Notes
- Run `php artisan migrate`. Staff now see Bookings (D-029).

## [M5] Public site & booking flow - 2026-10-01
### Added
- Public pages home, amenities, packages & rates (+ add-ons), gallery (category filter + Alpine lightbox), FAQ, contact, policies, sitemap.xml, robots.txt; all content from DB/settings, empty sections hidden (files: app/Http/Controllers/Public/PageController.php, app/Services/PublicContentService.php, resources/views/public/*.blade.php)
- Settings groups "Website content" and "SEO" (files: app/Enums/SettingGroup.php)
- Components x-ui.wave-divider, x-ui.section, x-ui.prose (safe Markdown-lite), x-public.page-hero, x-public.package-card, x-public.amenity-card (files: resources/views/components/ui/{wave-divider,section,prose}.blade.php, resources/views/components/public/*.blade.php)
- 3-step booking page with availability calendar and live quotes (Alpine), no-JS fallback, payment step, success page with copy button (files: app/Http/Controllers/Public/BookingController.php, resources/views/public/book/*.blade.php, resources/js/booking.js, resources/js/app.js)
- Track booking by reference + mobile with status timeline and proof upload/replace (files: app/Http/Controllers/Public/TrackBookingController.php, resources/views/public/track/*.blade.php)
- GuestBookingService, PaymentProofService (private disk, image re-encode/metadata strip), PhoneNumber, PhilippineMobile and Turnstile rules, new booking exceptions (files: app/Services/Booking/{GuestBookingService,PaymentProofService}.php, app/Support/PhoneNumber.php, app/Rules/*.php, app/Exceptions/Booking/{BookingWindow,PaymentProofNotAllowed,InvalidPaymentProof}Exception.php)
- Form Requests QuoteRequest, StoreBookingRequest, UploadPaymentProofRequest, TrackBookingRequest (files: app/Http/Requests/Public/*.php)
- Rate limiters booking-read/booking-write/proof-upload/track, honeypot, Turnstile config flag (files: app/Providers/AppServiceProvider.php, config/wonderpool.php, .env.example)
- ActivityAction payment.proof_uploaded; PriceBreakdown base/subtotal formatted fields (files: app/Enums/ActivityAction.php, app/Services/Booking/PriceBreakdown.php)
- Tests: pages/SEO, booking flow, EXIF stripping, quote/calendar endpoints, tracking, rate limits, Turnstile, phone normalization (files: tests/Feature/Public/*.php, tests/Unit/PhoneNumberTest.php)
### Changed
- Public layout: real nav, footer from settings, site data via view composer; head: per-page SEO, canonical, OG/Twitter tags (files: resources/views/layouts/public.blade.php, resources/views/partials/head.blade.php, app/Providers/AppServiceProvider.php)
- Routes for public pages, booking and tracking (files: routes/web.php)
- Docs: admin guide (website content), deployment (private proofs backup, Turnstile, upload limits) (files: docs/admin-guide.md, docs/deployment.md)
### Removed
- Temporary home view and static robots.txt (files: resources/views/home.blade.php, public/robots.txt)
### DB: none (new settings rows via SettingSeeder; run `php artisan db:seed --class=SettingSeeder`)
### Routes: added home (controller), amenities, packages, gallery, faq, contact, policies, sitemap, robots, book, book.availability, book.quote, book.store, book.payment, book.payment.store, book.done, track, track.lookup, track.show
### Breaking / Notes
- Run `php artisan db:seed --class=SettingSeeder` on existing databases to add the new content/SEO keys (never overwrites edits).

## [M4] Booking engine - 2026-10-01
### Added
- AvailabilityService: windows incl. midnight crossing, half-open overlap checks vs active bookings and blocked dates, conflicts(), two-query calendar map, booking-window check (files: app/Services/Booking/AvailabilityService.php)
- PricingService + PriceBreakdown: rules stacked by priority on the running price, add-ons, max_pax, centavo rounding, downpayment (files: app/Services/Booking/PricingService.php, app/Services/Booking/PriceBreakdown.php)
- ReferenceCodeGenerator WP-YYMM-XXXX (files: app/Services/Booking/ReferenceCodeGenerator.php)
- BookingService: create (advisory lock, re-check, price snapshot), transition (central map, reasons, approval stamp, completion guard, event), reschedule (re-check, optional reprice), expireStale (files: app/Services/Booking/BookingService.php)
- Booking exceptions and BookingStatusChanged event (files: app/Exceptions/Booking/*.php, app/Events/BookingStatusChanged.php)
- Exclusion constraint bookings_no_overlap (files: database/migrations/2026_10_01_001500_add_booking_overlap_exclusion_constraint.php)
- Command bookings:expire-stale scheduled every 15 minutes (files: app/Console/Commands/ExpireStaleBookings.php, routes/console.php)
- Admin Blocked dates (whole-day or exact range, overlap warning) and Pricing rules (live sample preview, quote checker) with Form Requests, policies, nav entries (files: app/Http/Controllers/Admin/{BlockedDate,PricingRule}Controller.php, app/Http/Requests/Admin/Content/{,Store,Update}{BlockedDate,PricingRule}Request.php, app/Policies/{BlockedDate,PricingRule}Policy.php, app/Services/Booking/BlockedDateService.php, resources/views/admin/{blocked-dates,pricing-rules}/*.blade.php, resources/views/layouts/admin.blade.php, routes/web.php)
- ActivityAction booking.created/status_changed/rescheduled/expired (files: app/Enums/ActivityAction.php)
- Tests: availability, pricing, reference codes, booking service (advisory-lock wait, stale pre-check, constraint + translation, full transition matrix, reschedule), expiry command, admin modules (files: tests/Feature/Booking/*.php, tests/Feature/Admin/Booking/*.php, tests/Pest.php)
### Changed
- BlockedDate and PricingRule use AdminListable (files: app/Models/BlockedDate.php, app/Models/PricingRule.php)
- BookingFactory default bookings get distinct far-future days (no accidental overlaps under the constraint) (files: database/factories/BookingFactory.php)
- Docs: scheduler cron, admin guide for pricing rules and blocked dates (files: docs/deployment.md, docs/admin-guide.md)
### DB: added 2026_10_01_001500_add_booking_overlap_exclusion_constraint (reversible)
### Routes: added admin.blocked-dates.*, admin.pricing-rules.* (resources except show)
### Breaking / Notes
- Run `php artisan migrate`; it fails if the database already holds overlapping pending/approved bookings (none in seed data).
- Production must run the Laravel scheduler (cron `schedule:run` every minute).

## [M3] Content modules - 2026-10-01
### Added
- Owner-only admin CRUD for packages, add-ons, amenities, gallery, FAQs: index with ILIKE search, active/inactive filter, pagination, empty states, confirm-delete modal, success/warning flashes (files: app/Http/Controllers/Admin/{Package,AddOn,Amenity,Gallery,Faq}Controller.php, resources/views/admin/{packages,add-ons,amenities,gallery,faqs}/*.blade.php, routes/web.php)
- Form Requests: shared base per module + Store/Update subclasses; package time coherence with crosses_midnight; pesos → centavos; shared image rules jpg/png/webp ≤5 MB ≤8000 px (files: app/Http/Requests/Admin/Content/*.php)
- Policies: ContentPolicy base + Package/AddOn/Amenity/GalleryImage/Faq policies (owner only); gate manage-content (files: app/Policies/*.php, app/Providers/AppServiceProvider.php)
- ContentService (create/update/delete/reorder + audit), GuardsDeletion contract + ContentInUseException (package with bookings, add-on used on bookings) (files: app/Services/Content/ContentService.php, app/Models/Contracts/GuardsDeletion.php, app/Exceptions/ContentInUseException.php, app/Models/Package.php, app/Models/AddOn.php)
- ImageService: original + WebP large (1600 px) and thumbs (480 px) on the public disk; AmenityService and GalleryService (multi-upload, visibility, file cleanup) (files: app/Services/Content/{ImageService,AmenityService,GalleryService}.php, app/Enums/ImageVariant.php)
- AdminListable model concern (search, whereState, adminLabel) on Package, AddOn, Amenity, GalleryImage, Faq; imageUrl() on Amenity/GalleryImage (files: app/Models/Concerns/AdminListable.php, app/Models/*.php)
- Reusable sortable list: Alpine `sortable` (drag-and-drop + keyboard) and x-admin.sortable / x-admin.sort-handle, used by packages, amenities, gallery, FAQs; PATCH reorder endpoints (files: resources/js/sortable.js, resources/js/app.js, resources/views/components/admin/sortable.blade.php, resources/views/components/admin/sort-handle.blade.php)
- Components x-admin.confirm-delete, x-admin.index-filters, x-admin.icon-picker, x-ui.checkbox, x-ui.file-input; button variant danger-ghost (files: resources/views/components/admin/*.blade.php, resources/views/components/ui/{checkbox,file-input,button}.blade.php)
- Curated amenity icon list (files: app/Support/AmenityIcons.php)
- ActivityAction content.created/updated/deleted/reordered (files: app/Enums/ActivityAction.php)
- Tests per module, delete guards, reorder endpoints and algorithm, image variants, staff 403 (files: tests/Feature/Admin/Content/*.php, tests/Feature/Services/ContentServiceTest.php)
### Changed
- Admin nav: Packages, Add-ons, Amenities, Gallery, FAQs (files: resources/views/layouts/admin.blade.php)
- Dependency intervention/image 3.11; CI enables gd (files: composer.json, composer.lock, .github/workflows/ci.yml)
- Docs: gd + storage:link requirements, upload limits, admin guide content section (files: README.md, docs/deployment.md, docs/admin-guide.md)
### DB: none (existing M1 tables)
### Routes: added admin.{packages,add-ons,amenities,gallery,faqs}.{index,create,store,edit,update,destroy}, admin.{packages,amenities,gallery,faqs}.reorder (PATCH), admin.gallery.toggle-visibility (PATCH)
### Breaking / Notes
- Requires the PHP gd extension (WebP) and `php artisan storage:link`.

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
