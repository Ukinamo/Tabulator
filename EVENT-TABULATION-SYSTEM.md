# 🏆 Web-Based Event Tabulation System
## System Design & Technical Documentation

> **Project:** Event Tabulation System for Luzon, Philippines  
> **Stack:** Laravel (REST API) · Vue.js (SPA) · MySQL/MariaDB · Laravel Sanctum / JWT  
> **Version:** 1.0.0  
> **Last Updated:** 2025

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Functional Requirements](#2-functional-requirements)
3. [User Roles and Permissions](#3-user-roles-and-permissions)
4. [Database Architecture](#4-database-architecture)
5. [Laravel Backend Architecture](#5-laravel-backend-architecture)
6. [Vue.js Frontend Architecture](#6-vuejs-frontend-architecture)
7. [UI Design Guidelines](#7-ui-design-guidelines)
8. [API Endpoint Reference](#8-api-endpoint-reference)
9. [Security Considerations](#9-security-considerations)

---

## 1. System Overview

The **Web-Based Event Tabulation System** is a real-time scoring and results management platform designed for live events held across **Luzon, Philippines**. It is purpose-built to support event types such as:

- 👑 Pageants and beauty contests
- 🎤 Talent competitions
- 🎭 Cultural and performing arts contests
- 🏅 Academic and skills competitions

The system digitizes the entire tabulation workflow — from contestant registration and criteria setup, through judge scoring, score validation, and final results publication. It eliminates manual computation errors and provides the **Event MC** with a controlled, reveal-by-reveal results display for maximum audience impact.

### How It Works

```
Super Admin / Organizer             Judges (Admin Role)
sets up event, contestants, ──────▶ score contestants ──────▶ submit scores
categories, and weights             per category

        │
        ▼
Super Admin reviews & approves scores
        │
        ▼
Final results are computed automatically
        │
        ▼
Event MC reveals winners one at a time during the live event
```

---

## 2. Functional Requirements

### 2.1 Authentication & Authorization

- Users must log in with email and password credentials.
- Session tokens are issued via **Laravel Sanctum** (or JWT as an alternative).
- Each user is assigned exactly one role: `super_admin`, `admin` (judge), `mc`, or `organizer`.
- Role-based middleware protects all API endpoints.
- Token expiry and refresh policies are enforced.

### 2.2 Contestant Management

- Super Admin can create, update, and delete contestant records.
- Each contestant is assigned a unique contestant number.
- Contestants are linked to a specific event.
- Contestant photos and bios can optionally be uploaded.

### 2.3 Category and Criteria Management

- Super Admin and Organizer can define contest categories (e.g., Talent Portion, Q&A, Attire Runway).
- Each category has a **weight** (percentage) that contributes to the final score.
- Each category contains one or more **judging criteria** with individual maximum scores.
- Total category weights across all categories must sum to **100%**.

### 2.4 Scoring System

- Judges view their assigned contestant-category matrix.
- Each judge scores contestants against defined criteria.
- Scores are bounded by the defined minimum (0) and maximum per criterion.
- Judges may edit scores **before final submission** (if permitted by the Super Admin).
- A judge cannot view scores submitted by other judges until results are published.

### 2.5 Score Submission Workflow

```
Judge enters scores
      │
      ▼
Scores saved as DRAFT (editable)
      │
      ▼
Judge clicks "Submit Scores"
      │
      ▼
Scores locked for that judge
      │
      ▼
Super Admin reviews all judge submissions
      │
      ▼
Super Admin approves scores
      │
      ▼
System calculates final results
```

### 2.6 Result Calculation

- The system computes weighted average scores per contestant across all judges and categories.
- Formula:

```
Final Score = Σ (Category Weight × Average Judge Score for that Category)
```

- Contestants are ranked from highest to lowest final score.
- Tie-breaking rules can be configured (e.g., higher score on the most heavily weighted category).

### 2.7 Result Publishing for the Event MC

- Super Admin approves and publishes final results to the MC dashboard.
- Results are revealed **one at a time** in a controlled sequence (e.g., 5th runner-up → 1st runner-up → winner).
- The MC clicks a "Reveal Next" button to advance through the results.
- The MC cannot skip ahead or modify any results.

---

## 3. User Roles and Permissions

### 3.1 Role Overview

| Permission | Super Admin | Admin (Judge) | Event MC | Organizer |
|---|:---:|:---:|:---:|:---:|
| Manage user accounts | ✅ | ❌ | ❌ | ❌ |
| Configure contestants | ✅ | ❌ | ❌ | ❌ |
| Configure categories & weights | ✅ | ❌ | ❌ | ✅ |
| Define judging criteria | ✅ | ❌ | ❌ | ✅ |
| Score contestants | ❌ | ✅ | ❌ | ❌ |
| Submit scores | ❌ | ✅ | ❌ | ❌ |
| Edit own scores (pre-submission) | ❌ | ✅ | ❌ | ❌ |
| Review all submitted scores | ✅ | ❌ | ❌ | ✅ (monitor only) |
| Approve & publish results | ✅ | ❌ | ❌ | ❌ |
| View final results | ✅ | ❌ | ✅ | ✅ |
| Reveal results (MC mode) | ❌ | ❌ | ✅ | ❌ |
| Monitor scoring progress | ✅ | ❌ | ❌ | ✅ |
| Full system access | ✅ | ❌ | ❌ | ❌ |

---

### 3.2 Super Admin

The Super Admin has full administrative control over the system.

**Responsibilities:**
- Create and manage judge (Admin) accounts, MC accounts, and Organizer accounts.
- Set up the event: name, date, venue, and general configuration.
- Add and manage contestants (name, number, photo, bio).
- Configure contest categories and assign weights.
- Lock or unlock score editing for judges.
- Review all submitted scores from each judge.
- Approve scores and trigger final result computation.
- Publish results and control what the Event MC can reveal.

---

### 3.3 Admin (Judge)

Judges are responsible solely for scoring contestants.

**Responsibilities:**
- Log in to their personal judge account.
- View the list of contestants and assigned categories.
- Enter scores for each contestant per criterion.
- Save scores as drafts and edit them before submission.
- Submit final scores to the Super Admin for review.
- View their own submitted scores (read-only after submission).

> ⚠️ Judges **cannot** view scores from other judges or see final rankings until published.

---

### 3.4 Event MC

The Event MC has a restricted, read-only interface for live result presentation.

**Responsibilities:**
- Log in to the MC dashboard.
- Wait for the Super Admin to publish results.
- Use the "Reveal Next" feature to unveil winners one at a time.
- View the current revealed results on screen.

> ⚠️ The MC has **no access** to the full rankings until each result is revealed in sequence.

---

### 3.5 Organizer

The Organizer assists in event configuration but has no scoring privileges.

**Responsibilities:**
- Define and manage judging criteria and category weights.
- Configure scoring rules and tie-breaking policies.
- Monitor real-time scoring progress (which judges have submitted).
- Assist in event setup and troubleshooting configuration issues.
- View scoring progress reports (without individual judge scores being exposed).

---

## 4. Database Architecture

### 4.1 Entity Relationship Summary

```
users ─────────────────────────────────────────────────┐
  │                                                     │
  │ (created_by)                                        │ (judge)
  ▼                                                     ▼
events ──────────┬──────────────────────────────────► scores
  │              │                                      ▲   ▲
  ▼              ▼                                      │   │
contestants   categories ──► criteria ─────────────────┘   │
  │                                                         │
  └─────────────────────────────────────────────────────────┘
  │
  ▼
results
```

---

### 4.2 Table: `users`

Stores all system users (Super Admin, Judges, MCs, Organizers).

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique user ID |
| `name` | VARCHAR(255) | NOT NULL | Full name |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | Login email |
| `password` | VARCHAR(255) | NOT NULL | Hashed password |
| `role` | ENUM | NOT NULL | `super_admin`, `admin`, `mc`, `organizer` |
| `is_active` | BOOLEAN | DEFAULT TRUE | Account active status |
| `created_by` | BIGINT UNSIGNED | FK → users.id, NULLABLE | Who created this user |
| `created_at` | TIMESTAMP | | Record creation time |
| `updated_at` | TIMESTAMP | | Record update time |

---

### 4.3 Table: `events`

Stores event configuration.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique event ID |
| `name` | VARCHAR(255) | NOT NULL | Event name |
| `description` | TEXT | NULLABLE | Event description |
| `venue` | VARCHAR(255) | NULLABLE | Event venue |
| `event_date` | DATE | NOT NULL | Date of the event |
| `status` | ENUM | NOT NULL, DEFAULT `setup` | `setup`, `ongoing`, `scoring`, `published` |
| `created_by` | BIGINT UNSIGNED | FK → users.id | Super Admin who created the event |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

---

### 4.4 Table: `contestants`

Stores contestant records per event.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique contestant ID |
| `event_id` | BIGINT UNSIGNED | FK → events.id, NOT NULL | Event this contestant belongs to |
| `contestant_number` | VARCHAR(20) | NOT NULL | Display number (e.g., "01", "02") |
| `name` | VARCHAR(255) | NOT NULL | Full name of contestant |
| `bio` | TEXT | NULLABLE | Short biography |
| `photo_url` | VARCHAR(500) | NULLABLE | URL to uploaded photo |
| `is_active` | BOOLEAN | DEFAULT TRUE | Whether contestant is still active |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Unique Constraint:** `(event_id, contestant_number)`

---

### 4.5 Table: `categories`

Stores contest segments/portions with their weights.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique category ID |
| `event_id` | BIGINT UNSIGNED | FK → events.id, NOT NULL | Event this category belongs to |
| `name` | VARCHAR(255) | NOT NULL | Category name (e.g., "Talent Portion") |
| `weight` | DECIMAL(5,2) | NOT NULL | Weight in percentage (e.g., 30.00) |
| `description` | TEXT | NULLABLE | Category description or instructions |
| `sort_order` | INT | DEFAULT 0 | Display order |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

---

### 4.6 Table: `criteria`

Stores individual judging criteria within a category.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique criterion ID |
| `category_id` | BIGINT UNSIGNED | FK → categories.id, NOT NULL | Parent category |
| `name` | VARCHAR(255) | NOT NULL | Criterion name (e.g., "Stage Presence") |
| `max_score` | DECIMAL(6,2) | NOT NULL | Maximum score for this criterion |
| `description` | TEXT | NULLABLE | Judging guidelines |
| `sort_order` | INT | DEFAULT 0 | Display order |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

---

### 4.7 Table: `scores`

Stores individual judge scores per contestant per criterion.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique score ID |
| `event_id` | BIGINT UNSIGNED | FK → events.id, NOT NULL | Event reference |
| `judge_id` | BIGINT UNSIGNED | FK → users.id, NOT NULL | Judge who submitted the score |
| `contestant_id` | BIGINT UNSIGNED | FK → contestants.id, NOT NULL | Contestant being scored |
| `criterion_id` | BIGINT UNSIGNED | FK → criteria.id, NOT NULL | Criterion being scored |
| `score` | DECIMAL(6,2) | NOT NULL | Actual score given |
| `status` | ENUM | DEFAULT `draft` | `draft`, `submitted`, `approved` |
| `submitted_at` | TIMESTAMP | NULLABLE | When judge submitted this score |
| `approved_at` | TIMESTAMP | NULLABLE | When Super Admin approved |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Unique Constraint:** `(judge_id, contestant_id, criterion_id)`

---

### 4.8 Table: `results`

Stores computed final results per contestant per event.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique result ID |
| `event_id` | BIGINT UNSIGNED | FK → events.id, NOT NULL | Event reference |
| `contestant_id` | BIGINT UNSIGNED | FK → contestants.id, NOT NULL | Contestant reference |
| `final_score` | DECIMAL(8,4) | NOT NULL | Computed weighted final score |
| `rank` | INT | NOT NULL | Final ranking position |
| `is_published` | BOOLEAN | DEFAULT FALSE | Whether published to MC |
| `is_revealed` | BOOLEAN | DEFAULT FALSE | Whether MC has revealed this result |
| `reveal_order` | INT | NULLABLE | Order in which MC reveals (1 = revealed last/winner) |
| `published_at` | TIMESTAMP | NULLABLE | When Super Admin published |
| `revealed_at` | TIMESTAMP | NULLABLE | When MC revealed |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Unique Constraint:** `(event_id, contestant_id)`

---

### 4.9 Relationship Summary

| Relationship | Type | Description |
|---|---|---|
| `events` → `contestants` | One-to-Many | One event has many contestants |
| `events` → `categories` | One-to-Many | One event has many categories |
| `categories` → `criteria` | One-to-Many | One category has many criteria |
| `users` → `scores` | One-to-Many | One judge submits many scores |
| `contestants` → `scores` | One-to-Many | One contestant receives many scores |
| `criteria` → `scores` | One-to-Many | One criterion has many scores across judges |
| `events` → `results` | One-to-Many | One event has many results |
| `contestants` → `results` | One-to-One (per event) | One contestant has one result per event |

---

## 5. Laravel Backend Architecture

### 5.1 Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── AuthController.php
│   │   ├── Admin/
│   │   │   ├── UserController.php
│   │   │   ├── EventController.php
│   │   │   ├── ContestantController.php
│   │   │   └── ScoreReviewController.php
│   │   ├── Judge/
│   │   │   ├── ScoreController.php
│   │   │   └── ContestantViewController.php
│   │   ├── Organizer/
│   │   │   ├── CategoryController.php
│   │   │   └── CriterionController.php
│   │   ├── MC/
│   │   │   └── ResultRevealController.php
│   │   └── ResultController.php
│   ├── Middleware/
│   │   ├── CheckRole.php
│   │   ├── EnsureEventIsActive.php
│   │   └── EnsureScoresNotLocked.php
│   └── Requests/
│       ├── StoreContestantRequest.php
│       ├── StoreCategoryRequest.php
│       ├── StoreScoreRequest.php
│       └── PublishResultsRequest.php
├── Models/
│   ├── User.php
│   ├── Event.php
│   ├── Contestant.php
│   ├── Category.php
│   ├── Criterion.php
│   ├── Score.php
│   └── Result.php
├── Services/
│   ├── ScoreCalculationService.php
│   ├── ResultPublishingService.php
│   └── ScoreValidationService.php
└── Policies/
    ├── EventPolicy.php
    ├── ScorePolicy.php
    └── ResultPolicy.php
```

---

### 5.2 Models

#### `User` Model

```php
class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'role', 'is_active', 'created_by'];

    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN       = 'admin';
    const ROLE_MC          = 'mc';
    const ROLE_ORGANIZER   = 'organizer';

    public function isSuperAdmin(): bool { return $this->role === self::ROLE_SUPER_ADMIN; }
    public function isJudge(): bool      { return $this->role === self::ROLE_ADMIN; }
    public function isMC(): bool         { return $this->role === self::ROLE_MC; }
    public function isOrganizer(): bool  { return $this->role === self::ROLE_ORGANIZER; }

    public function scores(): HasMany    { return $this->hasMany(Score::class, 'judge_id'); }
}
```

#### `Score` Model

```php
class Score extends Model
{
    protected $fillable = ['event_id', 'judge_id', 'contestant_id', 'criterion_id', 'score', 'status'];

    const STATUS_DRAFT     = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED  = 'approved';

    public function judge(): BelongsTo      { return $this->belongsTo(User::class, 'judge_id'); }
    public function contestant(): BelongsTo { return $this->belongsTo(Contestant::class); }
    public function criterion(): BelongsTo  { return $this->belongsTo(Criterion::class); }
}
```

---

### 5.3 API Routes (`routes/api.php`)

```php
// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes (authenticated)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Super Admin routes
    Route::middleware('role:super_admin')->prefix('admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('events', EventController::class);
        Route::apiResource('events.contestants', ContestantController::class);
        Route::get('scores/review', [ScoreReviewController::class, 'index']);
        Route::post('scores/{score}/approve', [ScoreReviewController::class, 'approve']);
        Route::post('events/{event}/publish', [ResultController::class, 'publish']);
        Route::post('results/{result}/unlock-reveal', [ResultController::class, 'unlockReveal']);
    });

    // Organizer routes
    Route::middleware('role:super_admin,organizer')->prefix('organizer')->group(function () {
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('categories.criteria', CriterionController::class);
        Route::get('events/{event}/progress', [CategoryController::class, 'scoringProgress']);
    });

    // Judge routes
    Route::middleware('role:admin')->prefix('judge')->group(function () {
        Route::get('contestants', [ContestantViewController::class, 'index']);
        Route::get('scoresheet', [ScoreController::class, 'scoresheet']);
        Route::post('scores', [ScoreController::class, 'store']);
        Route::put('scores/{score}', [ScoreController::class, 'update']);
        Route::post('scores/submit', [ScoreController::class, 'submitAll']);
    });

    // Event MC routes
    Route::middleware('role:mc')->prefix('mc')->group(function () {
        Route::get('results', [ResultRevealController::class, 'index']);
        Route::post('results/{result}/reveal', [ResultRevealController::class, 'reveal']);
    });
});
```

---

### 5.4 Role Middleware (`CheckRole.php`)

```php
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Unauthorized. Insufficient role permissions.'
            ], 403);
        }

        return $next($request);
    }
}
```

Register in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\CheckRole::class,
    ]);
})
```

---

### 5.5 Score Calculation Service

```php
class ScoreCalculationService
{
    public function computeResults(Event $event): Collection
    {
        $contestants = $event->contestants()->active()->get();
        $categories  = $event->categories()->with('criteria')->get();

        return $contestants->map(function ($contestant) use ($categories) {
            $finalScore = $categories->sum(function ($category) use ($contestant) {
                $criteriaIds = $category->criteria->pluck('id');

                $avgScore = Score::query()
                    ->where('contestant_id', $contestant->id)
                    ->whereIn('criterion_id', $criteriaIds)
                    ->where('status', Score::STATUS_APPROVED)
                    ->avg('score') ?? 0;

                return ($category->weight / 100) * $avgScore;
            });

            return ['contestant' => $contestant, 'final_score' => round($finalScore, 4)];
        })->sortByDesc('final_score')->values();
    }
}
```

---

## 6. Vue.js Frontend Architecture

### 6.1 Project Structure

```
src/
├── assets/
│   └── styles/
│       ├── main.css
│       └── variables.css         # Color variables from brand palette
├── components/
│   ├── common/
│   │   ├── AppHeader.vue
│   │   ├── AppSidebar.vue
│   │   ├── AppButton.vue
│   │   ├── AppModal.vue
│   │   ├── AppAlert.vue
│   │   ├── AppBadge.vue
│   │   └── LoadingSpinner.vue
│   ├── contestants/
│   │   ├── ContestantCard.vue
│   │   └── ContestantList.vue
│   ├── scoring/
│   │   ├── ScoreSheet.vue
│   │   ├── ScoreInput.vue
│   │   └── ScoreProgressBar.vue
│   ├── results/
│   │   ├── ResultRevealCard.vue
│   │   └── ResultRankingTable.vue
│   └── dashboard/
│       ├── JudgeProgressWidget.vue
│       └── CategoryWeightChart.vue
├── pages/
│   ├── auth/
│   │   └── LoginPage.vue
│   ├── super-admin/
│   │   ├── DashboardPage.vue
│   │   ├── ManageUsersPage.vue
│   │   ├── ManageContestantsPage.vue
│   │   ├── ManageCategoriesPage.vue
│   │   ├── ReviewScoresPage.vue
│   │   └── PublishResultsPage.vue
│   ├── judge/
│   │   ├── JudgeDashboardPage.vue
│   │   └── ScoringPage.vue
│   ├── mc/
│   │   └── ResultRevealPage.vue
│   └── organizer/
│       ├── OrganizerDashboardPage.vue
│       └── CriteriaConfigPage.vue
├── router/
│   └── index.js
├── stores/
│   ├── auth.js
│   ├── event.js
│   ├── contestants.js
│   ├── scores.js
│   └── results.js
├── services/
│   ├── api.js                    # Axios instance with interceptors
│   ├── authService.js
│   ├── contestantService.js
│   ├── scoreService.js
│   └── resultService.js
└── App.vue
```

---

### 6.2 Router Configuration (`router/index.js`)

```javascript
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  { path: '/login', component: () => import('@/pages/auth/LoginPage.vue'), meta: { public: true } },

  // Super Admin
  {
    path: '/admin',
    meta: { role: 'super_admin' },
    children: [
      { path: 'dashboard', component: () => import('@/pages/super-admin/DashboardPage.vue') },
      { path: 'users', component: () => import('@/pages/super-admin/ManageUsersPage.vue') },
      { path: 'contestants', component: () => import('@/pages/super-admin/ManageContestantsPage.vue') },
      { path: 'categories', component: () => import('@/pages/super-admin/ManageCategoriesPage.vue') },
      { path: 'scores/review', component: () => import('@/pages/super-admin/ReviewScoresPage.vue') },
      { path: 'results/publish', component: () => import('@/pages/super-admin/PublishResultsPage.vue') },
    ]
  },

  // Judge
  {
    path: '/judge',
    meta: { role: 'admin' },
    children: [
      { path: 'dashboard', component: () => import('@/pages/judge/JudgeDashboardPage.vue') },
      { path: 'scoring', component: () => import('@/pages/judge/ScoringPage.vue') },
    ]
  },

  // MC
  { path: '/mc', component: () => import('@/pages/mc/ResultRevealPage.vue'), meta: { role: 'mc' } },

  // Organizer
  {
    path: '/organizer',
    meta: { role: 'organizer' },
    children: [
      { path: 'dashboard', component: () => import('@/pages/organizer/OrganizerDashboardPage.vue') },
      { path: 'criteria', component: () => import('@/pages/organizer/CriteriaConfigPage.vue') },
    ]
  },
]

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()
  if (!to.meta.public && !auth.isAuthenticated) return next('/login')
  if (to.meta.role && auth.user?.role !== to.meta.role) return next('/unauthorized')
  next()
})
```

---

### 6.3 State Management with Pinia

#### `stores/auth.js`

```javascript
import { defineStore } from 'pinia'
import { authService } from '@/services/authService'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    isLoading: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    userRole: (state) => state.user?.role ?? null,
    isSuperAdmin: (state) => state.user?.role === 'super_admin',
    isJudge: (state) => state.user?.role === 'admin',
    isMC: (state) => state.user?.role === 'mc',
    isOrganizer: (state) => state.user?.role === 'organizer',
  },

  actions: {
    async login(credentials) {
      this.isLoading = true
      const { token, user } = await authService.login(credentials)
      this.token = token
      this.user = user
      localStorage.setItem('token', token)
    },
    async logout() {
      await authService.logout()
      this.token = null
      this.user = null
      localStorage.removeItem('token')
    },
  }
})
```

#### `stores/scores.js`

```javascript
export const useScoreStore = defineStore('scores', {
  state: () => ({
    scoresheet: {},       // { contestantId: { criterionId: score } }
    submissionStatus: 'idle',  // idle | draft | submitted
    isDirty: false,
  }),

  actions: {
    updateScore(contestantId, criterionId, value) {
      if (!this.scoresheet[contestantId]) this.scoresheet[contestantId] = {}
      this.scoresheet[contestantId][criterionId] = value
      this.isDirty = true
    },
    async saveDraft() {
      await scoreService.saveDraft(this.scoresheet)
      this.isDirty = false
    },
    async submitAll() {
      await scoreService.submitAll()
      this.submissionStatus = 'submitted'
    },
  }
})
```

---

### 6.4 API Service (`services/api.js`)

```javascript
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL + '/api',
  headers: { 'Accept': 'application/json' }
})

// Attach token to every request
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Handle 401 globally
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
```

---

## 7. UI Design Guidelines

### 7.1 Brand Color Palette

| Role | Hex Code | Usage |
|---|---|---|
| **Primary** | `#F23892` | Main CTAs, active nav links, primary buttons, highlights |
| **Secondary** | `#BCD1FF` | Backgrounds, cards, hover states, secondary buttons |
| **Accent / Indicator** | `#38F298` | Success states, score confirmations, progress indicators, live badges |

### 7.2 Color Application Guide

#### Buttons

```css
/* Primary Button - use for main actions */
.btn-primary {
  background-color: #F23892;
  color: #FFFFFF;
  border: none;
}
.btn-primary:hover {
  background-color: #d0206e;
}

/* Secondary Button - use for cancel, back, secondary actions */
.btn-secondary {
  background-color: #BCD1FF;
  color: #1a1a2e;
  border: 1px solid #a8bfee;
}

/* Accent Button - use for confirm / approve actions */
.btn-accent {
  background-color: #38F298;
  color: #0a2e1a;
}
```

#### Navigation & Sidebar

- Active navigation item: background `#F23892`, text white.
- Inactive items: text `#BCD1FF` on dark sidebar background.
- Sidebar background: deep navy `#0F1A3E` for contrast.

#### Dashboards & Cards

- Card backgrounds: white with a subtle `#BCD1FF` left border for visual hierarchy.
- Section headers: `#F23892` underline accent.
- Data tables: alternating rows using `#F9FBFF` (near-white blue tint) and white.

#### Alerts & Status Indicators

| Status | Color | Description |
|---|---|---|
| Success / Approved | `#38F298` | Scores submitted, results published |
| Warning / Pending | `#FFD166` | Awaiting submission, under review |
| Error / Rejected | `#FF4D6D` | Validation errors, failed actions |
| Info | `#BCD1FF` | Informational notices, tips |

#### Scoring Interface (Judge View)

- Score input fields: white background with `#F23892` border on focus.
- Progress bar fill: gradient from `#BCD1FF` → `#F23892` as scoring progresses.
- Submitted state banner: `#38F298` background with checkmark icon.

#### MC Results Reveal Screen

- Full-screen dark background (`#07091A`) for dramatic effect.
- Contestant name text: `#FFFFFF` bold.
- Rank badge: `#F23892` circular badge.
- "Reveal Next" button: prominent `#F23892` with glow effect using `box-shadow: 0 0 24px #F2389280`.
- Revealed indicator: `#38F298` animated pulse dot.

### 7.3 Typography

| Element | Font | Weight | Size |
|---|---|---|---|
| Page Title | Inter / Poppins | 700 | 28–32px |
| Section Heading | Inter | 600 | 20–24px |
| Body Text | Inter | 400 | 14–16px |
| Table Header | Inter | 600 | 13px |
| Badge / Label | Inter | 500 | 11–12px |

---

## 8. API Endpoint Reference

### 8.1 Authentication

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `POST` | `/api/auth/login` | Public | Authenticate and receive token |
| `POST` | `/api/auth/logout` | Any | Invalidate current token |
| `GET` | `/api/auth/me` | Any | Get authenticated user profile |

**POST /api/auth/login — Request:**
```json
{
  "email": "judge1@example.com",
  "password": "secret123"
}
```

**POST /api/auth/login — Response:**
```json
{
  "token": "1|abcXYZ...",
  "user": {
    "id": 3,
    "name": "Judge Maria Santos",
    "email": "judge1@example.com",
    "role": "admin"
  }
}
```

---

### 8.2 Contestants

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `GET` | `/api/admin/events/{event}/contestants` | Super Admin | List all contestants |
| `POST` | `/api/admin/events/{event}/contestants` | Super Admin | Add a contestant |
| `PUT` | `/api/admin/events/{event}/contestants/{id}` | Super Admin | Update contestant info |
| `DELETE` | `/api/admin/events/{event}/contestants/{id}` | Super Admin | Remove a contestant |
| `GET` | `/api/judge/contestants` | Judge | View contestant list (read-only) |

**POST /api/admin/events/{event}/contestants — Request:**
```json
{
  "contestant_number": "01",
  "name": "Maria Clara Reyes",
  "bio": "From Laguna, advocating for environmental sustainability.",
  "photo_url": "https://cdn.example.com/photos/maria-reyes.jpg"
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 5,
    "event_id": 1,
    "contestant_number": "01",
    "name": "Maria Clara Reyes",
    "bio": "From Laguna, advocating for environmental sustainability.",
    "photo_url": "https://cdn.example.com/photos/maria-reyes.jpg",
    "is_active": true
  }
}
```

---

### 8.3 Categories & Criteria

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `GET` | `/api/organizer/categories` | Admin / Organizer | List categories for event |
| `POST` | `/api/organizer/categories` | Organizer / Super Admin | Create a category |
| `PUT` | `/api/organizer/categories/{id}` | Organizer / Super Admin | Update category |
| `DELETE` | `/api/organizer/categories/{id}` | Super Admin | Delete category |
| `POST` | `/api/organizer/categories/{id}/criteria` | Organizer / Super Admin | Add criteria to category |

**POST /api/organizer/categories — Request:**
```json
{
  "event_id": 1,
  "name": "Talent Portion",
  "weight": 30.00,
  "description": "Contestant performs an original or prepared talent piece.",
  "sort_order": 1
}
```

---

### 8.4 Scores

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `GET` | `/api/judge/scoresheet` | Judge | Get judge's scoring matrix |
| `POST` | `/api/judge/scores` | Judge | Save or update a score (draft) |
| `POST` | `/api/judge/scores/submit` | Judge | Submit all scores for review |
| `GET` | `/api/admin/scores/review` | Super Admin | View all submitted scores |
| `POST` | `/api/admin/scores/{score}/approve` | Super Admin | Approve a score |

**POST /api/judge/scores — Request:**
```json
{
  "event_id": 1,
  "contestant_id": 5,
  "criterion_id": 3,
  "score": 87.50
}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 42,
    "judge_id": 3,
    "contestant_id": 5,
    "criterion_id": 3,
    "score": 87.50,
    "status": "draft"
  }
}
```

**POST /api/judge/scores/submit — Response:**
```json
{
  "message": "All scores submitted successfully.",
  "submitted_count": 24
}
```

---

### 8.5 Results

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `POST` | `/api/admin/events/{event}/publish` | Super Admin | Compute and publish results |
| `GET` | `/api/admin/events/{event}/results` | Super Admin / Organizer | View full rankings |
| `GET` | `/api/mc/results` | MC | View revealed results only |
| `POST` | `/api/mc/results/{result}/reveal` | MC | Reveal the next result |

**POST /api/admin/events/{event}/publish — Response:**
```json
{
  "message": "Results published successfully.",
  "results": [
    { "rank": 1, "contestant": "Maria Clara Reyes", "final_score": 92.3750 },
    { "rank": 2, "contestant": "Ana Liza Bautista", "final_score": 89.8125 },
    { "rank": 3, "contestant": "Rosa Mae Delos Santos", "final_score": 87.5000 }
  ]
}
```

**GET /api/mc/results — Response (MC sees only revealed results):**
```json
{
  "data": [
    {
      "reveal_order": 3,
      "rank": 3,
      "contestant_name": "Rosa Mae Delos Santos",
      "contestant_number": "07",
      "is_revealed": true
    }
  ],
  "next_reveal_available": true
}
```

---

## 9. Security Considerations

### 9.1 Role-Based Access Control (RBAC)

- Every API route is protected by both `auth:sanctum` middleware and the custom `role` middleware.
- Role checks are enforced **server-side** at the middleware layer — client-side role-hiding is UI only and not a security boundary.
- Policies (Laravel Gates/Policies) provide object-level authorization (e.g., a judge can only update their own scores).
- Super Admin is the only role that can create other user accounts.

```php
// ScorePolicy.php
public function update(User $user, Score $score): bool
{
    return $user->isJudge()
        && $score->judge_id === $user->id
        && $score->status === Score::STATUS_DRAFT;
}
```

---

### 9.2 Secure Score Submission

- Scores are **locked** upon submission (`status = submitted`). Further edits require Super Admin to explicitly unlock.
- Each score record stores `judge_id`; the API resolves the judge from the authenticated token — judge IDs cannot be spoofed in the request body.
- A judge can only view their own scores; the API filters by `auth()->id()`.
- Score values are validated server-side against `max_score` for the criterion.

---

### 9.3 Input Validation

All incoming requests use Laravel Form Requests for validation:

```php
// StoreScoreRequest.php
public function rules(): array
{
    $criterion = Criterion::findOrFail($this->criterion_id);

    return [
        'event_id'       => ['required', 'integer', 'exists:events,id'],
        'contestant_id'  => ['required', 'integer', 'exists:contestants,id'],
        'criterion_id'   => ['required', 'integer', 'exists:criteria,id'],
        'score'          => ['required', 'numeric', 'min:0', "max:{$criterion->max_score}"],
    ];
}
```

Additional validation rules:
- Category weights must sum to exactly **100** before the event can start.
- Contestant numbers must be unique per event.
- All user passwords must be at least 8 characters with mixed case and numbers.
- API accepts only `application/json` Content-Type to prevent MIME-based attacks.

---

### 9.4 Authentication Protection

| Measure | Implementation |
|---|---|
| Token-based auth | Laravel Sanctum Personal Access Tokens |
| Token storage (frontend) | `localStorage` with auto-clear on logout |
| HTTPS enforcement | Enforced via server config (Nginx/Apache) — never HTTP in production |
| Token expiry | Configurable (recommended: 8-hour expiry for event duration) |
| CORS policy | Restricted to frontend origin via `config/cors.php` |
| Rate limiting | `throttle:60,1` middleware on auth routes |
| SQL injection prevention | Eloquent ORM with parameterized queries — raw queries avoided |
| XSS prevention | Vue.js auto-escapes template bindings; avoid `v-html` with user input |
| Mass assignment protection | All models define explicit `$fillable` arrays |
| Environment secrets | All credentials stored in `.env` — never committed to version control |

---

### 9.5 Audit Trail (Recommended)

Consider logging critical actions for accountability:

| Action | Log Entry |
|---|---|
| Score submitted | `judge_id`, `event_id`, timestamp |
| Score approved | `super_admin_id`, `score_id`, timestamp |
| Results published | `super_admin_id`, `event_id`, timestamp |
| Result revealed by MC | `mc_id`, `result_id`, timestamp |
| User account created | `created_by`, `new_user_id`, `role`, timestamp |

Use Laravel's built-in `activity log` package (e.g., `spatie/laravel-activitylog`) or a custom `audit_logs` table.

---

*End of Documentation — Web-Based Event Tabulation System v1.0.0*

> 📌 **For Developers:** This document serves as the primary technical design reference. All API contracts, database schemas, and architecture decisions defined here should be treated as the source of truth during development. Changes must be reflected back in this document.