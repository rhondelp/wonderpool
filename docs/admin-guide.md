# Admin Guide

> Purpose: how resort staff use the admin panel day to day (non-technical).

## Signing in
- Open `/admin/login` and sign in with your email and password. After 5 wrong attempts you must wait a minute.
- First sign-in (or after the owner resets your password): you must choose a new password before anything else.
- Forgot your password? Ask the owner to set a temporary one (Users → Edit → Reset password).
- Change your password any time from the account menu (top right) → Change password.

## Users (owner only)
- Users → Add user: name, email, role (Owner = everything; Staff = bookings only) and a temporary password. Share it privately.
- Disable stops someone signing in immediately; Enable restores access. You cannot disable or demote yourself.
## Dashboard
- Pending approval, arrivals today, bookings in the next 7 days and (owner only) verified revenue this month, plus the next 5 upcoming bookings.
## Managing bookings
Owners and staff. Only the owner can override a price or void a verified payment.
- **Bookings** list: tabs per status, search by reference, name or mobile (any format), filter by package, stay dates and payment ("Proof to review", unpaid, partially paid, paid). Click a column title to sort.
- Open a booking to see the guest, schedule, price, payments and history. **View proof** shows the uploaded receipt (only admins can open it).
- **Approve**: confirms the guest's uploaded proof automatically; approval needs the downpayment to be paid. If it isn't, use **Record payment** first (cash, GCash, bank).
- **Reject** needs a reason the guest will see. **Cancel** frees the date (refunds are handled outside the system). **Mark completed** after the stay. **Reschedule** shows a calendar of free dates; tick "Recalculate the price" to use today's rates.
- **Reject** on a payment (wrong amount, unreadable) tells the guest to upload again.
- **Walk-in booking** (button on the list): book for someone at the counter or on the phone, even for today; optionally record the payment and approve right away.
- **Receipt** prints a confirmation; **PDF** downloads it.
## Managing rooms, cottages & amenities
Owner only. Every list has a search box (not case-sensitive) and an Active/Inactive filter. Drag rows by the grip, or use the up/down arrows, to change the order guests see; it saves automatically.
- **Packages:** name, code, price (₱), start/end time, "Ends the next day" for overnight/24-hour packages, max guests, active. A package with bookings cannot be deleted: untick Active to hide it. Price/time changes only affect new bookings.
- **Add-ons:** name, description, price, active. Add-ons used on bookings cannot be deleted; deactivate them instead.
- **Amenities:** name, description, icon (type to search the icon list), optional photo, active.
- **Gallery:** Upload images → pick up to 20 JPG/PNG/WebP files (5 MB each), a category and an optional caption. Use Hide/Show to control what guests see, Edit to change caption or category.
- **FAQs:** question, answer, active.
## Pricing rules & blocked dates (owner only)
- **Pricing rules** change the package price on certain days. Weekend rules need the days (e.g. Sat, Sun); holiday and season rules need dates. Value is a whole percent (e.g. 10 or -15) or pesos (e.g. 1000). Several matching rules all apply, lowest priority number first. The form shows a sample price; "Quote check" on the list shows the full price for any package and date. Changes never affect existing bookings.
- **Blocked dates** close the resort (maintenance, private use). Choose whole days or an exact time range. Existing bookings are not cancelled: if any overlap you will see their references so you can contact the guests.
- Unpaid pending bookings are cancelled automatically after the pending hold time (Settings → Booking rules) if no payment proof was uploaded.

## Reports & activity logs
- **Dashboard** (everyone): confirmed stays per month for the last six months and the next 30 days at a glance (confirmed, pending, blocked, open). The owner also sees revenue received per month. Hover a bar for its value or open "Show data" for the numbers.
- **Reports** (owner only): pick a preset (this month, last month, last 30 days, this year, last year) or any range up to two years, optionally one package. You see revenue received, booked value, confirmed stays, occupancy, a chart per day (ranges up to 62 days) or per month, bookings by status and a per-package table.
  - *Revenue received* = verified payments, counted on the day they were verified. *Booked value*, *stays* and *occupancy* = approved and completed bookings, counted on the stay date. Occupancy = days with at least one confirmed stay ÷ days in the range.
  - **Bookings CSV** downloads every booking whose stay starts in the range (guest details, status, total, paid, balance) and opens in Excel. **Summary PDF** downloads the figures as a printable page. Each download is recorded in the activity log because the CSV contains guest details.
- **Activity log** (owner only): every sign-in, booking action, payment, content and settings change, with who did it and when. Filter by person ("Guest / system" = website guests and automatic jobs), area, action and dates, or search a booking reference or any detail. Entries cannot be edited or deleted.

## Website content
- Settings → **Website content**: home headline and sub-headline, about text, highlights (one per line), house rules, cancellation policy and the "what happens next" note guests see after booking. Leave a field blank to hide that section. Use `**bold**` and lines starting with `- ` for lists.
- Settings → **SEO**: the description Google shows and the image used when the site is shared on Facebook.
- Settings → **Payment**: the GCash/bank instructions shown when guests book.

## Settings
- Owner only. Tabs: General, Booking rules (downpayment %, pending hold, lead time, max advance days), Payment instructions, Contact, Social links. Every change is recorded in the activity log.
