# Wonderpool Garden Resort: Booking & Management System

**Stack:** Laravel (latest stable) + Blade, PostgreSQL, Tailwind CSS, Blade Icons (Heroicons set), Poppins
**Goal:** A public website plus an admin panel where the owner can control almost everything (packages, prices, availability, content, bookings, users) without touching code.

---

## 1. Scope & Assumptions

| Item | Decision |
|---|---|
| Business model | One property, **exclusive use** per booking (one booking blocks the whole resort for its time window) |
| Packages | Day (A) ₱7,000, Night (D) ₱9,000 (7PM–5AM, max 50 pax), 24-Hour ₱15,000 (7AM–5AM next day, max 50 pax). **All editable in admin.** |
| Payment | Manual: guest uploads a screenshot of full payment/downpayment, admin approves or rejects |
| Users | Guests book without an account (name, phone, email). Admin/staff log in. |
| Out of scope (v1) | Online payment gateway, multi-property support, guest accounts |
| Assumption to confirm | Day package hours (proposed 7:00 AM–5:00 PM, configurable) and downpayment percentage (proposed 50%, configurable) |

---

## 2. Feature List

### 2.1 Public site
- **Home:** hero, highlights, amenities, packages preview, gallery strip, location map, CTA
- **Amenities:** adult pool, kiddie pool, 2 AC rooms, function hall, billiards, videoke, parking, etc. (all from DB)
- **Packages & Rates:** cards driven by the `packages` table
- **Gallery:** categorized images (pools, rooms, hall, events)
- **Availability calendar:** shows booked/blocked dates per package
- **Booking flow (3 steps):**
  1. Pick package and date, guest count. Live availability and price check.
  2. Guest details (name, phone, email, event type, notes) plus optional add-ons.
  3. Booking summary and payment instructions, upload proof, receive a **reference code**.
- **Track booking:** enter reference code + phone to see status (Pending, Approved, Rejected, Cancelled, Completed)
- **Contact page, FAQ, policies/house rules, Facebook link**

### 2.2 Admin panel (the "more admin controls" part)
| Module | Controls |
|---|---|
| **Dashboard** | Today's check-ins, pending approvals, monthly revenue, upcoming bookings, occupancy chart |
| **Bookings** | List/filter/search, view details, view payment proof, **approve / reject (with reason) / cancel / reschedule / mark completed**, create walk-in booking, internal notes, print receipt |
| **Payments** | Record payments (downpayment, balance), proof gallery, payment status per booking |
| **Packages** | CRUD: name, code, description, price, start/end time, crosses-midnight flag, max pax, active toggle, sort order |
| **Seasonal pricing** | Rules by date range, weekday/weekend, holiday (percentage or fixed override) |
| **Add-ons** | Extra hours, extra pax, cottage, videoke, etc. with price and active toggle |
| **Blocked dates** | Block a date/time range (maintenance, private use) with reason |
| **Amenities** | CRUD with icon picker, description, image, order |
| **Gallery** | Upload, categorize, reorder, toggle visibility |
| **Site content (CMS-lite)** | Hero text, about, FAQ, policies, contact info, social links, footer, SEO meta |
| **Settings** | Resort name, logo, colors accent, downpayment %, cancellation policy, payment instructions (GCash/bank details), booking lead time, max advance days, notification toggles |
| **Users & roles** | Owner (full), Staff (bookings only). Enable/disable, password reset |
| **Reports** | Revenue by range/package, bookings count, export CSV/PDF |
| **Activity log** | Who did what and when (approvals, price changes, deletions) |

---

## 3. Architecture

Keep Laravel conventions. Thin controllers, logic in services, validation in Form Requests.

```
app/
├── Enums/                 BookingStatus, PaymentStatus, PaymentType, UserRole
├── Http/
│   ├── Controllers/
│   │   ├── Public/        HomeController, BookingController, TrackBookingController, GalleryController
│   │   └── Admin/         DashboardController, BookingController, PackageController, ...
│   ├── Requests/          StoreBookingRequest, UploadPaymentProofRequest, StorePackageRequest, ...
│   └── Middleware/        EnsureUserIsActive
├── Models/                Booking, Package, AddOn, BlockedDate, PricingRule, Payment, Amenity, GalleryImage, Setting, ActivityLog, User
├── Policies/              BookingPolicy, PackagePolicy, ...
├── Services/
│   ├── Booking/           AvailabilityService, PricingService, BookingService, ReferenceCodeGenerator
│   ├── PaymentProofService.php
│   ├── SettingService.php     (cached key/value settings)
│   └── ActivityLogger.php
├── Notifications/         BookingReceived, BookingApproved, BookingRejected
└── View/Components/       x-ui.button, x-ui.card, x-ui.modal, x-admin.stat-card, ...
resources/views/
├── layouts/               public.blade.php, admin.blade.php
├── components/
├── public/                home, amenities, packages, gallery, booking/*, track, contact
└── admin/                 dashboard, bookings/*, packages/*, settings/*, ...
```

**Rules of the codebase**
- Controllers: max ~1 action's worth of orchestration, no business logic.
- Enums instead of magic strings for statuses.
- Every service class and public method has a PHPDoc block (purpose, params, return, throws).
- Money stored as **integer centavos** (or `decimal(10,2)`; pick one and stay consistent).
- Follow PSR-12, enforced by **Laravel Pint**; static analysis with **Larastan** (level 6+).
- Conventional Commits, feature branches, PRs into `main`.

---

## 4. Database Design (PostgreSQL)

> Note: JSON columns are jsonb; enum-backed fields are string columns validated by PHP enums.

```
users(id, name, email, password, role, is_active, timestamps)

packages(id, name, code, description, base_price, start_time, end_time,
         crosses_midnight, max_pax, is_active, sort_order, timestamps)

add_ons(id, name, description, price, is_active, timestamps)

pricing_rules(id, package_id nullable, name, type[weekend|holiday|season],
              starts_on nullable, ends_on nullable, days_of_week json nullable,
              adjustment_type[percent|fixed], adjustment_value, priority, is_active)

bookings(id, reference_code unique, package_id, guest_name, guest_phone, guest_email,
         event_type nullable, guest_count, notes nullable,
         starts_at datetime, ends_at datetime,        -- resolved window, used for overlap checks
         total_amount, downpayment_required,
         status, rejection_reason nullable, admin_notes nullable,
         approved_by nullable, approved_at nullable, timestamps, soft_deletes)
  INDEX(starts_at, ends_at), INDEX(status)

booking_add_ons(id, booking_id, add_on_id, quantity, unit_price)

payments(id, booking_id, type[downpayment|balance|full], amount, proof_path nullable,
         status[pending|verified|rejected], reference_no nullable,
         verified_by nullable, verified_at nullable, timestamps)

blocked_dates(id, starts_at, ends_at, reason, created_by, timestamps)

amenities(id, name, description, icon, image_path nullable, is_active, sort_order)
gallery_images(id, path, caption, category, is_visible, sort_order)
faqs(id, question, answer, sort_order, is_active)
settings(id, key unique, value text, group)
activity_logs(id, user_id nullable, action, subject_type, subject_id, properties json, created_at)
```

**Why `starts_at`/`ends_at` on the booking:** Night and 24-hour packages cross midnight. Storing the resolved window makes overlap detection a single simple query and freezes history even if package hours change later. Also **snapshot the price** (`total_amount`) on the booking so later price edits never alter old bookings.

---

## 5. Core Business Logic

### 5.1 Availability (`AvailabilityService`)
A requested window `[S, E)` is **unavailable** if any of these overlap it:
- Bookings with status `pending` or `approved` (`starts_at < E AND ends_at > S`)
- Rows in `blocked_dates`

Since use is exclusive, this one check also handles the cross-package conflicts (e.g. Day on Jun 5 ends 5PM, Night on Jun 5 starts 7PM, which is allowed; 24-Hour on Jun 5 conflicts with both).

### 5.2 Race-condition safety
Wrap booking creation in `DB::transaction()` and first take a PostgreSQL transaction-level advisory lock (`SELECT pg_advisory_xact_lock(<fixed key>)`), then re-check availability and insert. Locking only the overlapping rows (`lockForUpdate()`) cannot stop two requests that both see no overlap (there is no row to lock), so creation must be serialized; the advisory lock is released automatically on commit/rollback.

Optional second line of defense: a partial exclusion constraint on `bookings` (requires the `btree_gist` extension only if combined with equality columns):
`EXCLUDE USING gist (tsrange(starts_at, ends_at, '[)') WITH &&) WHERE (status IN ('pending','approved') AND deleted_at IS NULL)`.

### 5.3 Pending hold expiry
A `pending` booking without payment proof auto-expires after N hours (setting). A scheduled command (`bookings:expire-stale`) sets it to `cancelled` and frees the slot.

### 5.4 Pricing (`PricingService`)
`final = base_price → apply matching pricing_rules by priority → + add-ons`. Returns a value object (`PriceBreakdown`) used by both the live quote endpoint and the booking service, so the preview and the stored total always match.

### 5.5 Status lifecycle
```
pending ──(proof uploaded)──▶ pending (payment pending verification)
   │                              │
   ├──▶ rejected                  ├──▶ approved ──▶ completed
   └──▶ cancelled                 └──▶ cancelled
```
Transitions are centralized in `BookingService` and validated (no jumping from `rejected` to `completed`). Every transition writes an activity log entry and fires a notification.

### 5.6 Payment proof handling
- Accept jpg/png/webp/pdf, max 5 MB, validated by MIME type.
- Store on the **private** disk; serve through an authorized admin route (never a public URL).
- Re-encode/resize images to strip metadata and save space.

---

## 6. Routes Overview

```
Public
GET  /                         home
GET  /amenities, /packages, /gallery, /contact, /faq
GET  /book                     step 1
POST /book/quote               JSON: live availability + price
POST /book                     create booking
POST /book/{ref}/payment       upload proof
GET  /track                    lookup form
POST /track                    result page

Admin (prefix /admin, middleware: auth, active, role)
GET  /admin                    dashboard
resource: bookings (+ approve, reject, cancel, reschedule, complete, proof)
resource: packages, add-ons, pricing-rules, blocked-dates, amenities, gallery, faqs, users
GET/PUT /admin/settings/{group}
GET  /admin/reports (+ export)
GET  /admin/activity-log
```

---

## 7. UI / Design System

**Theme:** fresh pool + garden. Clean, airy, lots of whitespace, rounded cards, soft shadows, subtle water-wave dividers.

```js
// tailwind.config.js (excerpt)
theme: {
  extend: {
    fontFamily: { sans: ['Poppins', 'ui-sans-serif', 'system-ui'] },
    colors: {
      pool:   { 50:'#ecfeff', 100:'#cffafe', 300:'#67e8f9', 500:'#06b6d4', 600:'#0891b2', 700:'#0e7490', 900:'#164e63' },
      garden: { 50:'#f0fdf4', 100:'#dcfce7', 300:'#86efac', 500:'#22c55e', 600:'#16a34a', 700:'#15803d', 900:'#14532d' },
    },
  },
}
```

- Primary actions: `pool-600`. Secondary / success accents: `garden-600`. Backgrounds: `pool-50` to white gradients.
- Status badge colors: pending (amber), approved (garden), rejected (red), completed (pool), cancelled (gray).
- Poppins weights 400/500/600/700, self-hosted or via `@fontsource/poppins` with `font-display: swap`.
- Icons: `blade-ui-kit/blade-heroicons` (`<x-heroicon-o-sun class="w-5 h-5" />`).
- Mobile-first (most local guests book from phones). Admin panel: sidebar on desktop, drawer on mobile, tables collapse to cards.
- Accessibility: contrast AA, focus rings, labels on all inputs, `alt` text on images.
- Reusable Blade components for everything repeated (button, input, select, card, badge, modal, table, stat-card, empty-state).
- Interactivity: **Alpine.js** for modals/dropdowns/step form, `fetch` for the quote endpoint. No SPA framework needed.

---

## 8. Documentation & Code-Quality Standards

| Area | Standard |
|---|---|
| **README.md** | Overview, requirements, install steps, `.env` guide, seeding, running, testing, deployment |
| **docs/** | `architecture.md`, `database.md` (ERD), `booking-flow.md`, `admin-guide.md` (for the owner, non-technical), `deployment.md` |
| **Code comments** | PHPDoc on classes, service methods, scopes; inline comments explain *why*, not *what* |
| **Naming** | Descriptive English names, `PascalCase` classes, `camelCase` methods, `snake_case` DB |
| **Migrations** | One concern per migration, reversible `down()`, foreign keys with explicit `onDelete` |
| **Seeders** | Default packages (A/D/24H), amenities, settings, an owner account (password from `.env`) |
| **Factories** | For every model, used in tests |
| **Tooling** | Pint (style), Larastan (types), GitHub Actions running Pint + Larastan + tests on each PR |
| **Git** | `main` protected, feature branches, Conventional Commits, `CHANGELOG.md` |

---

## 9. Testing Plan (Pest or PHPUnit)

- **Unit:** `PricingService` (seasonal rules, add-ons), `AvailabilityService` (all overlap edge cases incl. midnight crossing, back-to-back Day then Night), status transition rules.
- **Feature:** booking creation, double-booking prevention, proof upload validation, admin approve/reject, role permissions (staff cannot edit prices/settings), blocked dates.
- **Manual QA checklist:** mobile booking flow, proof upload on slow network, calendar accuracy across timezone (`Asia/Manila` set in `config/app.php`).

---

## 10. Security Checklist

- CSRF on all forms (Blade default), Form Request validation everywhere
- Policies/gates for every admin action, role middleware on route groups
- Rate limiting on booking, upload, and login routes; honeypot field + optional Cloudflare Turnstile against spam bookings
- Private storage for payment proofs, signed/authorized access only
- Bcrypt passwords, forced owner password change on first login
- No secrets in git, `APP_DEBUG=false` in production, HTTPS only
- Track-booking lookup requires **reference code + phone** (prevents guessing)
- Regular DB backups (nightly `pg_dump` to off-server storage)

---

## 11. Notifications

- **Email** (Laravel Mail, queued): booking received (guest + owner), approved, rejected with reason, reminder 1 day before.
- Notification channel class is swappable, so **SMS** (e.g. a PH provider) can be added later without touching business logic.
- Owner toggles each notification type in Settings.

---

## 12. Phased Roadmap

| Phase | Deliverables | Est. |
|---|---|---|
| **0. Setup** | Laravel install, PostgreSQL, Tailwind/Vite, Poppins, Blade Icons, Pint/Larastan, CI, base layouts, design tokens | 1–2 days |
| **1. Data layer** | Migrations, models, enums, factories, seeders | 2 days |
| **2. Admin foundation** | Auth, roles, admin layout, dashboard shell, Settings + SettingService | 3 days |
| **3. Content modules** | Packages, add-ons, amenities, gallery, FAQs CRUD | 4 days |
| **4. Booking engine** | Availability, pricing, booking service, blocked dates, pricing rules, tests | 4–5 days |
| **5. Public site** | All pages, availability calendar, 3-step booking, proof upload, track booking | 5 days |
| **6. Admin bookings** | Approval workflow, payments, walk-in, reschedule, print receipt | 4 days |
| **7. Reports & logs** | Dashboard charts, reports + export, activity log | 3 days |
| **8. Notifications** | Email templates, reminders, scheduled tasks | 2 days |
| **9. Hardening** | Security review, performance (eager loading, caching), accessibility, SEO, full test pass | 3 days |
| **10. Deploy & handover** | Production setup, backups, owner training, `admin-guide.md` | 2 days |

**Total:** about 5–6 weeks part-time, about 3–4 weeks full-time.

---

## 13. Deployment

- PHP 8.2+ (match current Laravel requirement), PostgreSQL 15+, Nginx, SSL via Let's Encrypt.
- `php artisan optimize`, `npm run build`, queue worker via Supervisor, scheduler via cron (`* * * * * php artisan schedule:run`).
- `storage:link` for public images; payment proofs stay on the private disk.
- Zero-downtime deploy script (pull, composer install --no-dev, migrate --force, cache, restart queue).

---

## 14. Open Questions for the Owner

1. Day package hours? (proposed 7:00 AM–5:00 PM)
2. Required downpayment percentage, and how long to hold a pending booking without payment?
3. Is the ₱7,000 day rate also capped at 50 pax? Are extra guests or extra hours charged?
4. Refund/cancellation and reschedule policy to display?
5. Payment channels to list (GCash number, bank account name)?
6. Should Staff be able to approve bookings, or only the Owner?
7. Preferred logo/brand colors, or should we derive them from the Facebook page?
8. Notification preference: email only, or SMS too?

---

## 15. Suggested First Commands

```bash
composer create-project laravel/laravel wonderpool
cd wonderpool
composer require blade-ui-kit/blade-heroicons
composer require --dev laravel/pint larastan/larastan pestphp/pest
npm install -D tailwindcss @tailwindcss/forms postcss autoprefixer
npm install @fontsource/poppins alpinejs
php artisan make:enum BookingStatus   # then build the data layer in Phase 1
```
