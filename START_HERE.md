# 🚀 AGENT STARTER PROMPT
## Web-Based Event Tabulation System — Full Project Build

---

You are a **senior full-stack developer** AI agent.

You have been given two documents:

- `AGENT_BUILD_PROMPT.md` — your step-by-step build instructions
- `EVENT-TABULATION-SYSTEM.md` — the full system requirements and design reference

---

## YOUR FIRST TASK BEFORE WRITING ANY CODE

**Read both documents completely, in this order:**

1. Read `EVENT-TABULATION-SYSTEM.md` from top to bottom.
2. Read `AGENT_BUILD_PROMPT.md` from top to bottom.

After reading both, output a **Pre-Build Analysis Summary** in this exact format:

```
=== PRE-BUILD ANALYSIS SUMMARY ===

TECH STACK:
  Backend  : Laravel 11 + Laravel Sanctum
  Frontend : Vue 3 + Pinia + Vue Router
  Database : MySQL / MariaDB

DATABASE TABLES (list all):
  1. users
  2. events
  3. ...

LARAVEL CONTROLLERS (list all with methods):
  1. AuthController           — login, logout, me
  2. SuperAdmin\UserController — index, store, update, destroy
  3. ...

VUE VIEWS / PAGES (list all file paths):
  1. src/views/auth/LoginView.vue
  2. src/views/admin/DashboardView.vue
  3. ...

PINIA STORES (list all):
  1. stores/auth.ts
  2. stores/event.ts
  3. ...

SERVICE CLASSES (list all):
  1. ScoreService             — recalculate()
  2. ResultPublishingService  — publish(), revealNext()
  3. ScoresheetService        — getScoresheetForJudge(), getProgressForEvent()

DEMO ACCOUNTS (confirm all 4):
  superadmin@tabulation.com / admin123  → role: super_admin
  judge1@tabulation.com     / judge123  → role: admin
  mc@tabulation.com         / mc123     → role: mc
  organizer@tabulation.com  / organizer123 → role: organizer

SCORE FORMULA UNDERSTOOD:
  final_score = Σ ( category.weight / 100 × avg_judge_score_for_that_category )

MC REVEAL LOGIC UNDERSTOOD:
  - Results published by Super Admin first
  - MC reveals ONE result at a time via "Reveal Next" button
  - reveal_order: highest rank number revealed first, rank 1 (winner) revealed last
  - MC cannot skip ahead or see unrevealed results

ABSOLUTE RULES CONFIRMED: ✓ (list any you have questions about)

READY TO BUILD: YES
=== END OF SUMMARY ===
```

---

## AFTER THE SUMMARY — BEGIN BUILDING

Once you have output the summary, proceed immediately with **Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 5** exactly as described in `AGENT_BUILD_PROMPT.md`.

Follow these execution rules at all times:

### ✅ DO
- Work through each Phase and Step **in order** — do not skip ahead
- Fix **every** error (compile, runtime, SQL) before moving to the next step
- Write **complete, working code** for every file — no placeholders, no TODOs
- Run verification checks at the end of each Phase before proceeding
- Scope every database query to the authenticated user's role
- Apply the brand colors in every UI component:
  - Primary:   `#F23892` (buttons, active nav, badges)
  - Secondary: `#BCD1FF` (backgrounds, cards, hover states)
  - Accent:    `#38F298` (success states, confirmations, progress)

### ❌ DO NOT
- Write "coming soon", "TODO", or stub any function
- Use `float` or `int` for score or weight columns — always `DECIMAL`
- Hard-delete any score, contestant, or event record — use soft deletes
- Allow a judge to see another judge's scores before results are published
- Allow the MC to reveal more than one result per button click
- Skip the Pre-Build Analysis Summary
- Start Phase 3 (Frontend) before all Phase 2 (Backend) verification checks pass
- Start Phase 4 (Testing) before all Phase 3 components are complete

---

## ENVIRONMENT SETUP

Before running any commands, confirm the following are installed:

```
PHP        >= 8.2
Composer   >= 2.x
Node.js    >= 20.x
npm        >= 10.x
MySQL      >= 8.0  (or MariaDB >= 10.6)
Laravel    >= 11.x
```

Use these ports:
```
Laravel backend  → http://localhost:8000
Vue 3 frontend   → http://localhost:5173
MySQL database   → localhost:3306
```

---

## PROJECT FOLDER STRUCTURE

Create both projects side by side:

```
/your-workspace/
├── tabulation-api/            ← Laravel 11 backend
│   ├── app/
│   ├── database/
│   ├── routes/
│   └── ...
└── tabulation-frontend/       ← Vue 3 frontend
    ├── src/
    │   ├── views/
    │   ├── stores/
    │   ├── components/
    │   ├── composables/
    │   ├── layouts/
    │   └── router/
    └── ...
```

---

## PHASE COMPLETION GATES

You must explicitly confirm each gate before proceeding:

```
[ GATE 1 ] Pre-Build Analysis Summary output → proceed to Phase 2
[ GATE 2 ] php artisan migrate:fresh --seed runs with zero errors
            php artisan serve starts on port 8000
            php artisan route:list shows 35+ routes
            POST /api/v1/auth/login works for all 4 demo accounts
            → proceed to Phase 3

[ GATE 3 ] All Vue views created (no placeholder pages)
            npm run dev starts on port 5173 with zero console errors
            Login page renders correctly for all 4 roles
            → proceed to Phase 4

[ GATE 4 ] All backend API checks pass (from AGENT_BUILD_PROMPT Phase 4)
            All frontend UI checks pass (from AGENT_BUILD_PROMPT Phase 4)
            → proceed to Phase 5

[ GATE 5 ] Final Checklist complete — all boxes checked
            → PROJECT COMPLETE
```

---

## IF YOU GET STUCK

If you encounter a blocker at any step:

1. **State the exact error** — paste the full error message
2. **State what you tried** — describe the fix attempt
3. **Ask a specific question** — do not ask vague questions like "what should I do?"

Common issues to anticipate:

| Issue | Fix |
|---|---|
| CORS error in browser | Verify `config/cors.php` allows `http://localhost:5173` |
| 401 on all API calls | Verify Sanctum stateful domains and token is sent in `Authorization: Bearer` header |
| `spent` / `final_score` all zeros | Check that ScoreService::recalculate() is called in ExpenseSeeder / ScoreSeeder |
| Judge sees other judges' scores | Ensure `getScoresheetForJudge()` filters by `judge_id = auth()->id()` |
| MC can skip results | Ensure `revealNext()` queries `WHERE is_revealed = false ORDER BY reveal_order ASC LIMIT 1` |
| Category weights don't sum to 100 | Add server-side validation in `StoreCategoryRequest` |
| Vue router redirects wrong role | Check `router.beforeEach` maps all 4 roles to correct paths |

---

## BEGIN NOW

Start with Step 1: **Read both documents. Output the Pre-Build Analysis Summary. Then build.**

Good luck. Build something great. 🏆