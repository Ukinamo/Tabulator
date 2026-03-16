# Agent Build Prompt — Web-Based Event Tabulation System (Laravel 11 + Vue 3)

> **How to use this prompt:**
> Paste the entire contents of this file into your AI coding agent (Cursor, Claude, Copilot, Windsurf, etc.)
> along with the `TABULATION_SYSTEM_REQUIREMENTS.md` file. The agent will read the requirements document
> and build the entire project end-to-end.

---

## AGENT INSTRUCTIONS

You are a senior full-stack developer tasked with building a complete, production-ready
**Web-Based Event Tabulation System** for live events in Luzon, Philippines (pageants,
talent competitions, and similar contests).

You have been provided with a requirements document called `TABULATION_SYSTEM_REQUIREMENTS.md`.
**Read that file completely and carefully before writing a single line of code.**
Every decision you make — architecture, file names, database columns, API routes, component
names, business logic — must trace back to a specific section in that document.
Do not invent requirements. Do not simplify or skip anything.

---

## YOUR ABSOLUTE RULES

These rules override everything else. Violating any of them is not acceptable.

```
RULE 1  — Read TABULATION_SYSTEM_REQUIREMENTS.md fully before starting any code.
RULE 2  — Fix every error (compile, runtime, lint) before moving to the next step.
RULE 3  — Never stub, skip, or write "TODO" / "coming soon" in any file.
RULE 4  — All score and weight columns must be DECIMAL(6,2). Never use float or int for scores.
RULE 5  — Soft deletes on User, Contestant, Budget, Score. Never hard-delete event records.
RULE 6  — Every score create/update/delete must trigger ScoreService::recalculate().
RULE 7  — Every API query must be scoped to the authenticated user's role. No data leaks between judges.
RULE 8  — Every API response must follow the standard JSON envelope:
           { "success": bool, "data": any|null, "message": string, "errors"?: object }
RULE 9  — Every form must disable its submit button and show a spinner during the request.
RULE 10 — Every page must be fully responsive and usable at 375 px mobile width.
RULE 11 — After `php artisan migrate:fresh --seed`, the system must show real,
           meaningful pre-seeded data — no empty states, no missing scores or contestants.
RULE 12 — The four demo accounts must work immediately after seeding with no manual intervention:
           superadmin@tabulation.com / admin123
           judge1@tabulation.com     / judge123
           mc@tabulation.com         / mc123
           organizer@tabulation.com  / organizer123
RULE 13 — Judges must NEVER see scores submitted by other judges until results are published.
RULE 14 — Category weights across all categories in a single event must sum to exactly 100%.
RULE 15 — The MC results reveal screen must reveal only one result at a time in the configured
           reveal order. The MC cannot skip ahead or view unrevealed results.
```

---

## PHASE 1 — ANALYSIS (Do this before any code)

Before writing any file, do the following:

1. Open and read `TABULATION_SYSTEM_REQUIREMENTS.md` from top to bottom.
2. List every database table you need to create (Section 4 — Database Architecture).
3. List every Laravel controller and its methods (Section 5 — Backend Architecture).
4. List every Vue 3 page/view file path (Section 6 — Frontend Architecture).
5. List every Pinia store (Section 6).
6. List every Service class (Section 5.5).
7. Confirm you understand the score submission workflow (Section 2.5).
8. Confirm you understand the result calculation formula (Section 2.6).
9. Confirm you understand the MC reveal sequence logic (Section 2.7).
10. Confirm you understand all four role permission boundaries (Section 3).

Only after completing this analysis should you begin Phase 2.

---

## PHASE 2 — LARAVEL BACKEND

Work through every step in order. Do not skip ahead.

### Step B-1: Project Bootstrap

```bash
composer create-project laravel/laravel tabulation-api
cd tabulation-api
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

Configure `.env`:
```
APP_NAME="Event Tabulation System"
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tabulation_system
DB_USERNAME=root
DB_PASSWORD=
```

In `config/cors.php` set `allowed_origins` to `['http://localhost:5173']`.

In `bootstrap/app.php` register the Sanctum middleware on the `api` group.

Verify: `php artisan serve` starts with no errors before continuing.

---

### Step B-2: Migrations

Create one migration file per table **in this exact order** using the full column
definitions from **Section 4** of the requirements document:

1. Modify the default `users` migration — add `role ENUM('super_admin','admin','mc','organizer')`,
   `is_active BOOLEAN DEFAULT TRUE`, `created_by BIGINT UNSIGNED NULL FK → users.id`,
   and `deleted_at TIMESTAMP NULL`.

2. `create_events_table` — name, description, venue, event_date DATE, status ENUM
   (`setup`,`ongoing`,`scoring`,`published`), created_by FK → users.id.

3. `create_contestants_table` — event_id FK, contestant_number VARCHAR(20),
   name, bio TEXT NULL, photo_url VARCHAR(500) NULL, is_active BOOLEAN DEFAULT TRUE.
   Unique constraint on `(event_id, contestant_number)`.

4. `create_categories_table` — event_id FK, name, weight DECIMAL(5,2),
   description TEXT NULL, sort_order INT DEFAULT 0.

5. `create_criteria_table` — category_id FK, name, max_score DECIMAL(6,2),
   description TEXT NULL, sort_order INT DEFAULT 0.

6. `create_scores_table` — event_id FK, judge_id FK → users.id, contestant_id FK,
   criterion_id FK, score DECIMAL(6,2), status ENUM(`draft`,`submitted`,`approved`),
   submitted_at TIMESTAMP NULL, approved_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL.
   Unique constraint on `(judge_id, contestant_id, criterion_id)`.

7. `create_results_table` — event_id FK, contestant_id FK,
   final_score DECIMAL(8,4), rank INT, is_published BOOLEAN DEFAULT FALSE,
   is_revealed BOOLEAN DEFAULT FALSE, reveal_order INT NULL,
   published_at TIMESTAMP NULL, revealed_at TIMESTAMP NULL.
   Unique constraint on `(event_id, contestant_id)`.

Every financial/score column uses `DECIMAL`. Soft deletes on all tables except `results`.

```bash
php artisan migrate
```

Fix any SQL errors before continuing.

---

### Step B-3: Eloquent Models

Create models for: `User`, `Event`, `Contestant`, `Category`, `Criterion`, `Score`, `Result`.

For **every** model:
- Add `SoftDeletes` trait (all except `Result`)
- Populate `$fillable` with every column from the migration
- Define all relationships:

```
User      hasMany: Event (created_by), Contestant (via event), Score (judge_id)
Event     hasMany: Contestant, Category, Result; belongsTo: User (created_by)
Contestant belongsTo: Event; hasMany: Score, Result
Category  belongsTo: Event; hasMany: Criterion
Criterion belongsTo: Category; hasMany: Score
Score     belongsTo: User (judge), Contestant, Criterion, Event
Result    belongsTo: Event, Contestant
```

**`Score` model extras:**
```php
const STATUS_DRAFT     = 'draft';
const STATUS_SUBMITTED = 'submitted';
const STATUS_APPROVED  = 'approved';

public function scopeForJudge($query, int $judgeId)
{
    return $query->where('judge_id', $judgeId);
}

public function scopeForEvent($query, int $eventId)
{
    return $query->where('event_id', $eventId);
}
```

**`Result` model extras:**
```php
public function scopePublished($query)
{
    return $query->where('is_published', true);
}

public function scopeRevealed($query)
{
    return $query->where('is_revealed', true)->orderBy('reveal_order', 'asc');
}
```

**`Category` model extras:**
```php
// Scope to validate weights sum to 100 for an event
public static function validateWeightsForEvent(int $eventId): bool
{
    $total = static::where('event_id', $eventId)->sum('weight');
    return round($total, 2) == 100.00;
}
```

---

### Step B-4: Seeders

Implement every seeder exactly as described in **Section 6** of the requirements.
Run them in this order: `UserSeeder → EventSeeder → ContestantSeeder → CategorySeeder → CriterionSeeder → ScoreSeeder → ResultSeeder`.

**UserSeeder:** Create the four demo accounts:
```
Super Admin:  name="Super Admin"  email=superadmin@tabulation.com  password=admin123  role=super_admin
Judge 1:      name="Judge Maria Santos"  email=judge1@tabulation.com  password=judge123  role=admin
MC:           name="Event MC"  email=mc@tabulation.com  password=mc123  role=mc
Organizer:    name="Event Organizer"  email=organizer@tabulation.com  password=organizer123  role=organizer
```

**EventSeeder:** Create one demo event:
```
name:        "Buwan ng Wika 2025 Talent Search"
venue:       "Pampanga Civic Center, San Fernando, Pampanga"
event_date:  current date
status:      scoring
created_by:  Super Admin user ID
```

**ContestantSeeder:** Create 5 contestants for the demo event:
```
01 - Maria Clara Reyes       (Laguna)
02 - Ana Liza Bautista       (Bulacan)
03 - Rosa Mae Delos Santos   (Pampanga)
04 - Jennifer Cruz Mendoza   (Tarlac)
05 - Cynthia Grace Villanueva (Bataan)
```

**CategorySeeder:** Create 3 categories for the demo event summing to 100%:
```
Talent Portion   — weight: 40%  sort_order: 1
Q&A Portion      — weight: 35%  sort_order: 2
Attire & Runway  — weight: 25%  sort_order: 3
```

**CriterionSeeder:** Create criteria for each category:
```
Talent Portion:
  - Stage Presence        max_score: 30
  - Performance Quality   max_score: 40
  - Audience Impact       max_score: 30

Q&A Portion:
  - Relevance of Answer   max_score: 40
  - Clarity & Delivery    max_score: 30
  - Confidence            max_score: 30

Attire & Runway:
  - Outfit Appropriateness  max_score: 40
  - Poise & Grace           max_score: 35
  - Overall Impression      max_score: 25
```

**ScoreSeeder:** Create submitted and approved scores for Judge 1 across all contestants
and criteria. After inserting all scores, call `ScoreService::recalculate($event)` to
populate the `results` table.

**ResultSeeder:** After scores are seeded, ensure results are computed and published.
Set `is_published = true`, assign `rank` values, and set `reveal_order` (5 = 5th runner-up, 1 = winner).
Set `is_revealed = false` for all results so the MC can perform the live reveal.

Wire everything in `DatabaseSeeder::run()` in the correct order.

```bash
php artisan db:seed
```

Verify: run `SELECT contestant_id, final_score, rank FROM results ORDER BY rank;` — values must be non-zero and ranked.

---

### Step B-5: Services

Create `app/Services/ScoreService.php`:

```php
// recalculate(Event $event): void
// 1. For each contestant in the event:
//    a. Fetch all APPROVED scores for this contestant
//    b. For each category:
//       i.  Average the judge scores for all criteria in that category
//       ii. Multiply by (category weight / 100)
//    c. Sum all weighted category averages → final_score
// 2. Upsert results table with computed final_score
// 3. Rank all contestants by final_score DESC, assign rank 1 = highest

// Formula:
// final_score = Σ ( category.weight/100 × average_judge_score_for_that_category )
```

Create `app/Services/ResultPublishingService.php`:

```php
// publish(Event $event, int $superAdminId): void
// 1. Verify all judges have submitted scores (status = submitted or approved)
// 2. Call ScoreService::recalculate($event)
// 3. Set results.is_published = true, published_at = now()
// 4. Assign reveal_order: rank N = reveal_order 1 (revealed first/last runner-up),
//    rank 1 = reveal_order N (revealed last/winner)
// 5. Set event.status = 'published'

// revealNext(Event $event, int $mcId): Result|null
// 1. Find the next unrevealed result ordered by reveal_order ASC
// 2. Set is_revealed = true, revealed_at = now()
// 3. Return the revealed Result with contestant relationship loaded
// 4. If no more unrevealed results, return null
```

Create `app/Services/ScoresheetService.php`:

```php
// getScoresheetForJudge(int $judgeId, int $eventId): array
// Returns a structured matrix:
// [
//   contestant => [
//     category => [
//       criterion => { id, name, max_score, current_score, status }
//     ]
//   ]
// ]
// Only returns this judge's own scores — never exposes other judges' scores

// getProgressForEvent(int $eventId): array
// Returns per-judge submission status:
// [ { judge_id, judge_name, submitted_count, total_count, status } ]
// Used by Super Admin and Organizer to monitor scoring progress
```

---

### Step B-6: Form Request Validation

Create Form Request classes for every write operation. Each must override
`failedValidation()` to return the standard 422 JSON envelope.

| Class | Key Rules |
|---|---|
| `StoreEventRequest` | name required; event_date required date; status in enum |
| `StoreContestantRequest` | contestant_number required unique per event; name required |
| `UpdateContestantRequest` | same as Store, all sometimes |
| `StoreCategoryRequest` | name required; weight DECIMAL > 0; total weights for event must not exceed 100 |
| `UpdateCategoryRequest` | same as Store, all sometimes |
| `StoreCriterionRequest` | name required; max_score > 0 DECIMAL; category_id exists |
| `StoreScoreRequest` | score required DECIMAL >= 0; score <= criterion.max_score; contestant_id exists in event; criterion_id exists |
| `UpdateScoreRequest` | score required; only allowed if current status = draft |
| `PublishResultsRequest` | event_id required; all judge scores must be submitted |

---

### Step B-7: Controllers

Create all controllers in `app/Http/Controllers/Api/` organized by role sub-folder.

Every controller method must:
- Verify the authenticated user's role via middleware before any logic runs
- Scope ALL Eloquent queries to the correct event/user context
- Return the standard JSON envelope `{ success, data, message, errors? }`
- Use the correct HTTP status codes (200, 201, 204, 401, 403, 404, 422)
- Delegate non-trivial logic to Service classes

---

**`AuthController`**
- `login()`    — validate credentials, issue Sanctum token, return `{ token, user }`
- `logout()`   — revoke current token
- `me()`       — return authenticated user profile with role

---

**`SuperAdmin\UserController`** — middleware: `role:super_admin`
- `index()`   — list all users (filter by role)
- `store()`   — create judge, MC, or organizer account
- `update()`  — update name, email, is_active
- `destroy()` — soft delete user (cannot delete self)

**`SuperAdmin\EventController`** — middleware: `role:super_admin`
- `index()`   — list all events with status
- `store()`   — create a new event
- `update()`  — update event name, venue, date
- `publish()` — call `ResultPublishingService::publish($event)` → returns computed results
- `unlockScoring($judgeId)` — reset a judge's submitted scores back to draft status

**`SuperAdmin\ScoreReviewController`** — middleware: `role:super_admin`
- `index()`   — list all submitted scores across all judges for an event
- `approve($scoreId)` — set score status = approved
- `approveAll()` — approve all submitted scores for an event

**`SuperAdmin\ContestantController`** — middleware: `role:super_admin`
- Full CRUD for contestants within an event
- `destroy()` checks: if contestant has approved scores, return 422

---

**`Organizer\CategoryController`** — middleware: `role:super_admin,organizer`
- Full CRUD for categories within an event
- `store()` and `update()` must validate that total weights ≤ 100 after the change
- `destroy()` guard: if category has linked criteria with submitted scores, return 422

**`Organizer\CriterionController`** — middleware: `role:super_admin,organizer`
- Full CRUD for criteria within a category
- `destroy()` guard: if criterion has any submitted scores, return 422

**`Organizer\ProgressController`** — middleware: `role:super_admin,organizer`
- `index($eventId)` — call `ScoresheetService::getProgressForEvent()` → return per-judge status

---

**`Judge\ScoreController`** — middleware: `role:admin`
- `scoresheet($eventId)` — call `ScoresheetService::getScoresheetForJudge(auth()->id(), $eventId)`
- `store()`  — save/upsert a single score with status = draft
- `update($id)` — update draft score (403 if already submitted)
- `submitAll($eventId)` — set all this judge's draft scores to submitted, lock editing
- `myScores($eventId)` — return only this judge's own scores (never other judges')

---

**`MC\ResultController`** — middleware: `role:mc`
- `index($eventId)` — return only already-revealed results (is_revealed = true), ordered by reveal_order
- `revealNext($eventId)` — call `ResultPublishingService::revealNext($event)` → return the revealed result
- `hasMore($eventId)` — return `{ has_more: bool, next_reveal_order: int|null }`

---

**`ResultController`** — middleware: `role:super_admin,organizer`
- `index($eventId)` — return full ranked results (published and unpublished) for admin/organizer view

---

### Step B-8: API Routes

In `routes/api.php`, register all routes under the `v1` prefix:

```php
Route::prefix('v1')->group(function () {

    // Public routes
    Route::prefix('auth')->group(function () {
        Route::post('login',  [AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('me',      [AuthController::class, 'me'])->middleware('auth:sanctum');
    });

    // All protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Super Admin
        Route::middleware('role:super_admin')->prefix('admin')->group(function () {
            Route::apiResource('users',       SuperAdmin\UserController::class);
            Route::apiResource('events',      SuperAdmin\EventController::class);
            Route::post('events/{event}/publish',         [SuperAdmin\EventController::class, 'publish']);
            Route::post('events/{event}/unlock/{judge}',  [SuperAdmin\EventController::class, 'unlockScoring']);
            Route::apiResource('events.contestants',      SuperAdmin\ContestantController::class);
            Route::get('scores/review',                   [SuperAdmin\ScoreReviewController::class, 'index']);
            Route::post('scores/{score}/approve',         [SuperAdmin\ScoreReviewController::class, 'approve']);
            Route::post('events/{event}/scores/approve-all', [SuperAdmin\ScoreReviewController::class, 'approveAll']);
            Route::get('results/{event}',                 [ResultController::class, 'index']);
        });

        // Organizer + Super Admin
        Route::middleware('role:super_admin,organizer')->prefix('organizer')->group(function () {
            Route::apiResource('categories',        Organizer\CategoryController::class);
            Route::apiResource('categories.criteria', Organizer\CriterionController::class);
            Route::get('events/{event}/progress',   [Organizer\ProgressController::class, 'index']);
        });

        // Judge
        Route::middleware('role:admin')->prefix('judge')->group(function () {
            Route::get('events/{event}/scoresheet',     [Judge\ScoreController::class, 'scoresheet']);
            Route::post('scores',                        [Judge\ScoreController::class, 'store']);
            Route::put('scores/{score}',                 [Judge\ScoreController::class, 'update']);
            Route::post('events/{event}/scores/submit', [Judge\ScoreController::class, 'submitAll']);
            Route::get('events/{event}/my-scores',      [Judge\ScoreController::class, 'myScores']);
        });

        // Event MC
        Route::middleware('role:mc')->prefix('mc')->group(function () {
            Route::get('events/{event}/results',       [MC\ResultController::class, 'index']);
            Route::post('events/{event}/results/reveal', [MC\ResultController::class, 'revealNext']);
            Route::get('events/{event}/results/has-more', [MC\ResultController::class, 'hasMore']);
        });
    });
});
```

Run `php artisan route:list` and confirm all 35+ routes are registered before continuing.

---

### Step B-9: Backend Verification

Before starting the frontend, verify the entire backend with these checks:

```
✓ php artisan migrate:fresh --seed  — zero errors
✓ php artisan serve                 — running on port 8000
✓ php artisan route:list            — all 35+ routes visible

✓ POST /api/v1/auth/login
    body: { email: superadmin@tabulation.com, password: admin123 }
    expect: 200, token returned

✓ GET /api/v1/judge/events/1/scoresheet  (Judge Bearer token)
    expect: 200, structured scoresheet matrix with all contestants and criteria

✓ POST /api/v1/judge/scores  (Judge Bearer token)
    body: { event_id:1, contestant_id:1, criterion_id:1, score:85 }
    expect: 201, score saved as draft

✓ GET /api/v1/admin/scores/review  (Super Admin token)
    expect: 200, array of submitted judge scores

✓ GET /api/v1/mc/events/1/results  (MC token)
    expect: 200, only is_revealed=true results (empty if none revealed yet)

✓ GET /api/v1/judge/events/1/scoresheet  (Super Admin token)
    expect: 403 Forbidden — wrong role

✓ GET /api/v1/admin/scores/review  (without token)
    expect: 401 Unauthenticated

✓ SELECT final_score, rank FROM results ORDER BY rank;
    — non-zero values, correctly ranked
```

Do not start Phase 3 until all backend checks pass.

---

## PHASE 3 — VUE 3 FRONTEND

Work through every step in order.

### Step F-1: Project Bootstrap

```bash
npm create vue@latest tabulation-frontend
# Select: Vue Router ✓, Pinia ✓, ESLint ✓
cd tabulation-frontend
npm install axios pinia-plugin-persistedstate @heroicons/vue dayjs
```

`vite.config.ts` — configure proxy:
```ts
server: {
  proxy: {
    '/api': 'http://localhost:8000'
  }
}
```

`src/main.ts` — register Pinia with `pinia-plugin-persistedstate`.

Brand colors — add to `src/assets/main.css`:
```css
:root {
  --color-primary:   #F23892;
  --color-secondary: #BCD1FF;
  --color-accent:    #38F298;
  --color-primary-dark: #d0206e;
}
```

---

### Step F-2: API Composable

Create `src/composables/useApi.ts`:

```ts
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

const api = axios.create({
  baseURL: '/api/v1',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use(config => {
  const auth = useAuthStore()
  if (auth.token) config.headers.Authorization = `Bearer ${auth.token}`
  return config
})

api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      useAuthStore().clear()
      router.push('/auth/login')
    }
    return Promise.reject(error)
  }
)

export default api
```

This composable is the **only** way any page or store should call the API.

---

### Step F-3: Pinia Stores

Create all stores in `src/stores/`:

**`stores/auth.ts`**
```ts
// State:  user (object|null), token (string|null), isLoading (bool)
// Getters: isAuthenticated, userRole, isSuperAdmin, isJudge, isMC, isOrganizer
// Actions: login(email, password), logout(), fetchUser(), clear()
// Persist: token only
```

**`stores/event.ts`**
```ts
// State:  currentEvent (object|null), events[], loading
// Actions: fetchAll(), fetchOne(id), create(data), update(id, data)
//          setCurrentEvent(event)
```

**`stores/contestants.ts`**
```ts
// State:  contestants[], loading
// Actions: fetchAll(eventId), create(eventId, data),
//          update(eventId, id, data), remove(eventId, id)
```

**`stores/categories.ts`**
```ts
// State:  categories[], loading, weightTotal (computed)
// Actions: fetchAll(eventId), create(data), update(id, data), remove(id)
//          fetchCriteria(categoryId)
```

**`stores/scores.ts`**
```ts
// State:  scoresheet (nested object), submissionStatus, isDirty, loading
// Actions: fetchScoresheet(eventId), saveScore(data), updateScore(id, data),
//          submitAll(eventId), fetchMyScores(eventId)
```

**`stores/results.ts`**
```ts
// State:  results[], revealedResults[], hasMore (bool), loading
// Actions: fetchResults(eventId), revealNext(eventId),
//          checkHasMore(eventId), publishResults(eventId)
```

**`stores/admin.ts`**
```ts
// State:  users[], scoreReview[], judgingProgress[], loading
// Actions: fetchUsers(), createUser(data), updateUser(id, data),
//          fetchScoreReview(eventId), approveScore(id), approveAll(eventId),
//          fetchProgress(eventId), unlockJudgeScores(eventId, judgeId)
```

---

### Step F-4: Router & Route Guards

Configure `src/router/index.ts` with all routes and meta roles:

```ts
const routes = [
  // Auth (no layout, public)
  { path: '/auth/login', component: () => import('@/views/auth/LoginView.vue'), meta: { public: true } },

  // Super Admin
  { path: '/admin/dashboard',   component: () => import('@/views/admin/DashboardView.vue'),   meta: { role: 'super_admin' } },
  { path: '/admin/users',       component: () => import('@/views/admin/UsersView.vue'),        meta: { role: 'super_admin' } },
  { path: '/admin/event',       component: () => import('@/views/admin/EventSetupView.vue'),   meta: { role: 'super_admin' } },
  { path: '/admin/contestants', component: () => import('@/views/admin/ContestantsView.vue'),  meta: { role: 'super_admin' } },
  { path: '/admin/scores',      component: () => import('@/views/admin/ScoreReviewView.vue'),  meta: { role: 'super_admin' } },
  { path: '/admin/results',     component: () => import('@/views/admin/ResultsView.vue'),      meta: { role: 'super_admin' } },

  // Organizer
  { path: '/organizer/dashboard', component: () => import('@/views/organizer/DashboardView.vue'), meta: { role: 'organizer' } },
  { path: '/organizer/categories', component: () => import('@/views/organizer/CategoriesView.vue'), meta: { role: 'organizer' } },
  { path: '/organizer/criteria',   component: () => import('@/views/organizer/CriteriaView.vue'),   meta: { role: 'organizer' } },
  { path: '/organizer/progress',   component: () => import('@/views/organizer/ProgressView.vue'),   meta: { role: 'organizer' } },

  // Judge
  { path: '/judge/dashboard',  component: () => import('@/views/judge/DashboardView.vue'), meta: { role: 'admin' } },
  { path: '/judge/scoresheet', component: () => import('@/views/judge/ScoresheetView.vue'), meta: { role: 'admin' } },

  // MC
  { path: '/mc/reveal', component: () => import('@/views/mc/RevealView.vue'), meta: { role: 'mc' } },

  // Redirect by role after login
  { path: '/', redirect: '/auth/login' },
]

// Navigation guard
router.beforeEach((to) => {
  const auth = useAuthStore()
  if (!to.meta.public && !auth.isAuthenticated) return '/auth/login'
  if (to.meta.role && auth.userRole !== to.meta.role) return '/unauthorized'
})

// After login — redirect to role-appropriate dashboard
// super_admin → /admin/dashboard
// admin       → /judge/dashboard
// mc          → /mc/reveal
// organizer   → /organizer/dashboard
```

---

### Step F-5: Layouts

**`src/layouts/AuthLayout.vue`:**
- Full-height gradient background using `--color-primary` (#F23892) to deep magenta
- Centered white card (max-width: 420px, rounded-2xl, shadow-xl)
- System title "🏆 Event Tabulation System" at top
- `<slot />` for form content
- No sidebar, no navigation

**`src/layouts/AdminLayout.vue`** (for super_admin, organizer):
- `<AppSidebar />` — fixed left panel on md:+ screen, hidden on mobile
- `<AppNavbar />` — sticky top bar: left=hamburger+logo, right=user role badge + avatar dropdown (name, role, logout)
- `<AppMobileNav />` — fixed bottom bar on mobile only
- `<main>` with `ml-0 md:ml-64` and `pb-20 md:pb-0`

**`src/layouts/JudgeLayout.vue`** (for judge/admin role):
- Simplified top navbar only — shows judge name, event name, submission status badge
- No sidebar — judges have only 2 pages
- `<main>` full-width content

**`src/layouts/MCLayout.vue`** (for mc role):
- Full-screen dark background (#07091A) — immersive reveal experience
- Minimal navbar: just event name centered and logout button top-right
- No other navigation — MC only has one screen

---

### Step F-6: Shared UI Components

Build these reusable components before any pages:

**`components/ui/AppButton.vue`**
Props: `label`, `type` (primary|secondary|danger|accent), `loading` (bool), `disabled`
- Shows spinner + disables when `loading = true`
- Primary: background `--color-primary` (#F23892), white text
- Secondary: background `--color-secondary` (#BCD1FF), dark text
- Accent: background `--color-accent` (#38F298), dark text

**`components/ui/AppBadge.vue`**
Props: `label`, `variant` (success|warning|danger|info|neutral)
Small pill badge with color-coded background.

**`components/ui/ConfirmDialog.vue`**
Props: `open`, `title`, `message`, `confirmLabel`, `confirmVariant`
Emits: `@confirm`, `@cancel`
Modal overlay with cancel and confirm buttons.

**`components/ui/EmptyState.vue`**
Props: `title`, `description`, `icon`, `actionLabel`, `actionTo`
Centered icon + title + description + optional CTA.

**`components/ui/LoadingSkeleton.vue`**
Props: `rows` (default 3), `type` (card|table|list)
Animated pulsing gray blocks.

**`components/ui/AlertBanner.vue`**
Props: `type` (warning|danger|success|info), `message`, `dismissable`
Color-coded banner with dismiss button.

**`components/cards/ContestantCard.vue`**
Props: `contestant` (object with number, name, photo_url, is_active)
Displays: contestant number badge (primary color), photo or avatar placeholder,
name, active/inactive badge, Edit + Delete buttons emitting `@edit` and `@delete`.

**`components/cards/CategoryWeightCard.vue`**
Props: `category` (with name, weight, criteria count)
Shows weight as a colored progress bar (green if under 100%, red if over).
Edit + Delete buttons.

**`components/scoring/ScoreInputRow.vue`**
Props: `criterion` (name, max_score), `currentScore`, `status`
- Number input bounded to [0, max_score]
- Emits `@change` on input
- Disabled (read-only) if status = submitted

**`components/scoring/JudgeProgressBadge.vue`**
Props: `judge` (name), `submitted` (int), `total` (int), `status`
Shows: judge name, progress fraction (e.g. "24/27"), colored status badge.

**`components/results/ResultRevealCard.vue`**
Props: `result` (rank, contestant_name, contestant_number, final_score), `isNew` (bool)
- Dark card with gold/silver/bronze accents for ranks 1–3
- Contestant number badge in primary color (#F23892)
- Animated entrance when `isNew = true` (fade + scale in)
- Final score displayed to 4 decimal places

---

### Step F-7: Auth View

**`src/views/auth/LoginView.vue`** (layout: AuthLayout)
- Email and password fields with validation (required, valid email)
- On submit: call `auth.login(email, password)`
  - On success: redirect based on role (super_admin→/admin/dashboard, admin→/judge/dashboard, mc→/mc/reveal, organizer→/organizer/dashboard)
  - On 401/422: show inline error "Invalid email or password."
- Disabled + spinner on button while loading
- No registration link (accounts are created by Super Admin only)
- Show demo credentials in a collapsible info box below the form

---

### Step F-8: Super Admin Views

**`src/views/admin/DashboardView.vue`**
- Event status banner (event name, date, status badge)
- 4 stat cards: Total Contestants, Total Judges, Categories Configured, Scores Submitted
- Judge progress table: each judge row shows name + progress bar + submitted/total + status badge
- Quick actions: "Review Scores" button → `/admin/scores`, "Publish Results" button (disabled unless all scores submitted)
- `<LoadingSkeleton>` while loading

**`src/views/admin/UsersView.vue`**
- Table: Name, Email, Role (badge), Status (active/inactive badge), Actions
- "Create User" button opens a modal form:
  - Fields: name, email, password, role (select: judge/mc/organizer), is_active
  - On save: `admin.createUser(data)` + toast
- Edit inline (same modal pre-filled)
- Deactivate toggle (set is_active = false) — not hard delete
- Cannot delete or deactivate own account (Super Admin's own row has no actions)

**`src/views/admin/EventSetupView.vue`**
- Form: Event name, venue, event_date
- Read-only event status display (setup/ongoing/scoring/published badge)
- Save button → `event.update(id, data)` + toast

**`src/views/admin/ContestantsView.vue`**
- Table: Number, Name, Photo (avatar), Status, Actions
- "Add Contestant" button → inline modal form (number, name, bio, photo_url)
- Edit + Soft Delete (with `<ConfirmDialog>`)
- Drag-to-reorder contestant numbers (optional but preferred)

**`src/views/admin/ScoreReviewView.vue`**
- Filter row: Judge select dropdown, Contestant select dropdown
- Score grid table: Judge rows × Criterion columns, cells show score value + status badge
- "Approve All" button at top-right: calls `admin.approveAll(eventId)` + toast
- Per-score "Approve" button in each cell (if not yet approved)
- "Unlock Judge" button per judge row (lets judge re-edit their scores)
- Color coding: draft=gray, submitted=yellow, approved=green

**`src/views/admin/ResultsView.vue`**
- Full ranked results table: Rank, Contestant Number, Name, Final Score (4 decimals)
- "Publish Results" button (primary) — triggers `results.publishResults(eventId)`
  - Shows confirmation dialog: "Are you sure? This will make results visible to the MC."
  - On success: all results marked published + toast
- Status: shows "Results Published" banner if already published
- Results table is read-only (no editing of computed scores)

---

### Step F-9: Organizer Views

**`src/views/organizer/DashboardView.vue`**
- Event info card (name, date, status)
- Weight summary: donut chart showing category weight distribution
- Judging progress table (same as admin dashboard, read-only)
- Alert if total category weights ≠ 100% (red `<AlertBanner>`)
- Alert if any category has zero criteria (yellow `<AlertBanner>`)

**`src/views/organizer/CategoriesView.vue`**
- List of `<CategoryWeightCard>` components
- Total weight indicator at top: "75% / 100%" with color bar
- "Add Category" form (name, weight %, description, sort_order)
- Edit inline; Delete with guard (422 = "Cannot delete: has submitted scores")
- Weight validation: real-time warning if total would exceed 100% on add/edit

**`src/views/organizer/CriteriaView.vue`**
- Category selector at top (dropdown)
- For selected category: list of criteria with name, max_score, sort_order
- "Add Criterion" inline form
- Edit + Delete per row (delete guard if scores submitted against it)

**`src/views/organizer/ProgressView.vue`**
- Table of all judges with per-judge submission progress
- Columns: Judge Name, Submitted Scores, Total Required, % Complete, Status
- Auto-refreshes every 30 seconds via `setInterval`
- Color-coded status: Not Started (gray) / In Progress (yellow) / Submitted (green)

---

### Step F-10: Judge Views

**`src/views/judge/DashboardView.vue`**
- Welcome message: "Welcome, [Judge Name]"
- Event info: event name, date, venue
- Submission status card:
  - Green banner: "You have submitted your scores." (if submitted)
  - Yellow banner: "X scores remaining." (if draft)
- "Go to Scoresheet" button → `/judge/scoresheet`
- My submission summary: table of my scores by category (averages only, no other judges)

**`src/views/judge/ScoresheetView.vue`**
- Top bar: event name, contestant selector (tabs or dropdown), submission status badge
- For each selected contestant, show a scoring card per category:
  - Category name + weight badge
  - Each criterion row: criterion name, max score chip, `<ScoreInputRow>` input
  - Category subtotal shown below
- Sticky bottom bar: "Save Draft" button + "Submit All Scores" button
  - "Save Draft" → `scores.saveScore(data)` (saves one at a time or in bulk)
  - "Submit All" → shows `<ConfirmDialog>` "Once submitted, scores are locked. Proceed?" → `scores.submitAll(eventId)`
- After submission: all inputs become read-only, "Submitted" badge in top bar
- `<AlertBanner type="warning">` if any score fields are empty before submission attempt

---

### Step F-11: MC View

**`src/views/mc/RevealView.vue`** (layout: MCLayout — full-screen dark)

This is the most visually critical screen. Build it with drama and clarity.

Layout:
- Top center: Event name in white, large font
- Center stage: revealed results stack (most recently revealed at top)
- Bottom center: "Reveal Next Winner" button (primary color, large, glowing)

Behavior:
1. On mount: call `results.fetchResults(eventId)` and `results.checkHasMore(eventId)`
2. Show already-revealed results as `<ResultRevealCard>` (no animation — already revealed)
3. "Reveal Next Winner" button:
   - Disabled if `!results.hasMore`
   - On click: show brief 3-second "suspense" countdown animation (3… 2… 1…)
   - Then call `results.revealNext(eventId)`
   - New `<ResultRevealCard>` slides in from bottom with animation
   - Button re-evaluates `has_more`
4. When `has_more = false` after reveal: button changes to "✓ All Winners Revealed" (disabled, accent color)
5. Confetti animation when rank 1 (winner) is revealed
6. "Waiting for results to be published..." overlay if `is_published = false`

---

## PHASE 4 — INTEGRATION TESTING

After both backend and frontend are complete, run through every check below.
Fix any failure before considering the project done.

### Backend API Checks

```
✓ POST /api/v1/auth/login
    body: { email: superadmin@tabulation.com, password: admin123 }
    expect: 200, { success: true, data: { token, user: { role: "super_admin" } } }

✓ POST /api/v1/auth/login
    body: { email: judge1@tabulation.com, password: judge123 }
    expect: 200, token for judge

✓ GET /api/v1/judge/events/1/scoresheet  (Judge token)
    expect: 200, full contestant × criterion matrix

✓ POST /api/v1/judge/scores  (Judge token)
    body: { event_id:1, contestant_id:1, criterion_id:1, score:87.5 }
    expect: 201, score saved as draft

✓ POST /api/v1/judge/scores  (Super Admin token — wrong role)
    expect: 403 Forbidden

✓ POST /api/v1/judge/events/1/scores/submit  (Judge token)
    expect: 200, { submitted_count: N }

✓ GET /api/v1/admin/scores/review  (Super Admin token)
    expect: 200, judge scores including just-submitted ones

✓ POST /api/v1/admin/events/1/publish  (Super Admin token)
    expect: 200, ranked results array with final_score values

✓ GET /api/v1/mc/events/1/results  (MC token — before any reveal)
    expect: 200, { data: [] }  (empty — nothing revealed yet)

✓ POST /api/v1/mc/events/1/results/reveal  (MC token)
    expect: 200, { data: { rank: 5, contestant_name: "...", final_score: "..." } }

✓ GET /api/v1/admin/scores/review  (MC token — wrong role)
    expect: 403 Forbidden

✓ GET /api/v1/budgets  (no token — wrong endpoint, just checking 401)
    expect: 401 Unauthenticated
```

### Frontend UI Checks

```
✓ /auth/login — login as each of the 4 demo accounts, redirects to correct dashboard
✓ /admin/dashboard — event info, 4 stat cards, judge progress table visible
✓ /admin/users — 4 users listed, Create User modal opens and works
✓ /admin/contestants — 5 pre-seeded contestants listed with numbers
✓ /admin/scores — score grid populated from seeder, Approve All works
✓ /admin/results — ranked results table, Publish Results button enabled
✓ /judge/dashboard — correct judge name, submission status shown
✓ /judge/scoresheet — all contestants selectable, score inputs functional, Submit works
✓ /organizer/categories — 3 categories listed, total weight = 100% indicator green
✓ /organizer/progress — judge progress table with auto-refresh
✓ /mc/reveal — "Waiting for results..." shown if not published
✓ /mc/reveal — after publishing, "Reveal Next" button active
✓ /mc/reveal — reveals one contestant at a time with animation
✓ /mc/reveal — after all revealed, button changes to "All Winners Revealed"
✓ Resize to 375 px — all pages usable, no horizontal scroll, bottom nav on mobile
✓ Zero "Coming soon" anywhere in the running application
✓ Color scheme: buttons use #F23892 primary, secondary uses #BCD1FF, accents #38F298
```

---

## PHASE 5 — FINAL CHECKLIST

Confirm every item before declaring the project complete:

```
□ php artisan migrate:fresh --seed    — zero errors, all tables populated
□ php artisan serve                   — backend running on localhost:8000
□ npm run dev                         — frontend running on localhost:5173
□ All 35+ API routes registered       — php artisan route:list confirms
□ Full CRUD: events, contestants, categories, criteria — all working
□ Score draft → submit → approve → publish flow — end-to-end working
□ Final score formula correct: Σ(weight/100 × avg_judge_score_per_category)
□ Judges cannot see other judges' scores at any point before publishing
□ MC can only reveal one result at a time in correct order
□ MC cannot access /admin or /judge routes (403 enforced)
□ Category weights must sum to 100% — validated on save
□ Soft deletes active: deleted scores/contestants not included in calculations
□ Mobile layout (375 px): all 4 role dashboards usable, no overflow
□ No "Coming soon" anywhere in the app
□ 4 demo accounts → each lands on correct dashboard after login
□ Color palette applied: #F23892 primary, #BCD1FF secondary, #38F298 accent
□ All form submit buttons disabled + spinner during API calls
□ Every page has a loading skeleton while data loads
□ Every empty list shows an EmptyState component, not a blank page
□ CORS allows localhost:5173 — no browser CORS errors in console
□ All score values display to correct decimal precision
□ Reveal screen has dramatic animation and confetti on winner reveal
```

---

*Agent Build Prompt — Web-Based Event Tabulation System (Laravel 11 + Vue 3)*
*Use alongside: TABULATION_SYSTEM_REQUIREMENTS.md*
*Version 1.0 — March 2026*
*Designed for events in Luzon, Philippines*