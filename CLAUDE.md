# Wonderpool Garden Resort — Agent Rules

Online booking system for Wonderpool Garden Resort: public site (rooms, cottages, amenities, availability, booking requests with payment proof) plus an admin panel (content, bookings, reports, logs, notifications). Full scope: PLAN.md.
**Stack:** Laravel + Blade, PostgreSQL, Tailwind CSS, blade-heroicons, Poppins font, Alpine.js. Blue-green "pool/garden" theme.

## Token-saving rules
a. At the start of every task, read HISTORY.md and the top 3 entries of CHANGELOG.md. They are the source of truth for project structure.
b. Do NOT scan, list, or grep the whole project to "get oriented". Use the Folder Map, Routes Table, and Services/Models index in HISTORY.md to locate files; open only files you will edit.
c. Read PLAN.md only for the sections relevant to the current phase.
d. Do not re-read files you just wrote.

## Code quality rules
- PSR-12; run `vendor/bin/pint` before committing.
- Thin controllers; business logic in `app/Services`; validation in Form Requests.
- Enums (`app/Enums`) instead of magic strings.
- PHPDoc on every class and public method.
- Money stored consistently (see HISTORY.md decisions).
- Conventional Commits; no secrets in git.
- Tests (Feature/Unit) for all business logic.

## Design rules
- Tailwind tokens `pool-*` and `garden-*`; Poppins font.
- Reusable Blade components: `x-ui.*` (public/shared), `x-admin.*` (admin).
- Mobile-first, WCAG AA contrast, no inline styles.

## Database rules (PostgreSQL)
- Use Eloquent or the query builder; if raw SQL is unavoidable use PostgreSQL syntax only (ILIKE, DATE_TRUNC, TO_CHAR, COALESCE; never DATE_FORMAT, IFNULL or backticks); LIKE is case-sensitive so search with ILIKE; JSON columns are jsonb; enum-backed columns are string columns validated by PHP enums; store emails lowercase; quote reserved words such as "group" in raw SQL; never combine lockForUpdate with count() or other aggregates; tests run on PostgreSQL (wonderpool_test), never SQLite.

## Repository
https://github.com/rhondelp/wonderpool.git (PUBLIC). Default branch: `main`.

## Git rules
a. Never commit directly to main after the bootstrap. Phase start:
   `git checkout main && git pull origin main && git checkout -b phase/M<id>-<slug>` (e.g. `phase/M4-booking-engine`). Post-launch changes: `change/<slug>`.
b. Repo is PUBLIC. NEVER commit .env, real credentials, API keys, payment proofs, uploaded user files, DB dumps, or personal data. Only `.env.example` with placeholders. Run `git status` before every commit and verify nothing sensitive is staged.
c. Small Conventional Commits with the milestone ID, e.g. `feat(M4): add availability service`. Several commits per phase are fine.
d. `git push -u origin <branch>`. If `gh` is available and authenticated, open a PR into main (title `M<id>: <Title>`, body = milestone report summary); otherwise print `https://github.com/rhondelp/wonderpool/compare/main...<branch>`.
e. Never merge your own PR, never force-push main, never rewrite pushed history.

## End-of-task checklist (mandatory, in order)
1. Run `vendor/bin/pint` and the test suite; fix failures.
2. Update CHANGELOG.md (new entry at top).
3. Update HISTORY.md (edit only affected lines; do not rewrite the file).
4. Update MILESTONES.md (status table row + milestone report).
5. Commit (Conventional Commit with milestone ID), then record the final commit hash in the MILESTONES.md row and report, and commit that as a follow-up `docs(M<id>): record milestone report`.
6. Push the phase branch and open the PR (see Git rules).
7. Reply with a max 10-line summary incl. branch name and PR/compare link, then STOP. Wait for review and merge before the next phase.
