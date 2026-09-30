# HISTORY — Project Memory

> Keep under ~500 lines. Edit affected lines only. Every entry references a milestone ID.

## Project Summary
- Wonderpool Garden Resort booking system: public booking site + admin panel. (M0)
- Scope and roadmap: PLAN.md (§12 = roadmap). Tracking: CHANGELOG.md, MILESTONES.md.

## Stack & Versions
| Component | Version | Milestone |
|---|---|---|
| PHP | _tbd_ | M0 |
| Laravel | _tbd_ | M0 |
| MySQL | _tbd_ | M0 |
| Tailwind CSS | _tbd_ | M0 |
| Alpine.js | _tbd_ | M0 |
| blade-heroicons | _tbd_ | M0 |
| Font | Poppins | M0 |

## Conventions & Decisions
| ID | Date | Decision | Reason | Milestone |
|---|---|---|---|---|
| D-000 | 2026-10-01 | Repo `https://github.com/rhondelp/wonderpool.git` (PUBLIC), default branch `main`. Bootstrap commit only on main; then one branch per phase `phase/M<id>-<slug>`, post-launch `change/<slug>`; Conventional Commits with milestone ID; PR into main, reviewed/merged by owner only; no force-push/history rewrite. | Reviewable phases; public repo requires strict secret hygiene. | M0 |

## Folder Map
| Path | Purpose | Milestone |
|---|---|---|
| `/` | CLAUDE.md, PLAN.md, HISTORY.md, CHANGELOG.md, MILESTONES.md | M0 |

## Routes Table
| Method | URI | Name | Controller@action | Middleware | Milestone |
|---|---|---|---|---|---|

## Database Tables
| Table | Key columns | Relations | Milestone |
|---|---|---|---|

## Models & Relationships
- _none yet_

## Enums
| Name | Cases | Milestone |
|---|---|---|

## Services Index
| Class | Public methods (purpose) | Milestone |
|---|---|---|

## Blade Components
| Tag | Props | Used in | Milestone |
|---|---|---|---|

## Settings Keys
| Key | Group | Default | Meaning | Milestone |
|---|---|---|---|---|

## Scheduled Commands & Jobs
| Command/Job | Schedule | Purpose | Milestone |
|---|---|---|---|

## Notifications & Mail Templates
| Class | Channel(s) | Trigger | Template | Milestone |
|---|---|---|---|---|

## Env Variables
| Name | Purpose | Milestone |
|---|---|---|

## Business Flow Summaries
### Booking flow
- _tbd (M4/M5)_
### Status lifecycle
- _tbd (M4)_
### Pricing
- _tbd (M4)_
### Availability
- _tbd (M4)_

## Known Issues / TODO
- PLAN.md was not present at bootstrap; add it to the repo root before M0 starts. (M0)
