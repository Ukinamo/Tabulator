# Project CRUD Analysis & Test Report

## 1. Project Overview

**Event Tabulation System** — Laravel 11 + Vue 3 + Inertia.js. Roles: Super Admin, Organizer, Judge, MC. Backend: REST API under `/api/v1` with role-based middleware; frontend: Inertia pages per role.

---

## 2. CRUD Matrix (Backend API)

| Resource      | List (Index) | Create (Store) | Read (Show) | Update | Delete | Notes |
|---------------|--------------|----------------|-------------|--------|--------|--------|
| **Users**     | ✅ GET /admin/users | ✅ POST /admin/users | ✅ GET /admin/users/{id} | ✅ PUT /admin/users/{id} | ✅ DELETE (soft) | Cannot delete/update self. Role restricted to admin/mc/organizer. |
| **Events**    | ✅ GET /admin/events | ✅ POST /admin/events | ✅ GET /admin/events/{id} | ✅ PUT /admin/events/{id} | ❌ 403 | Destroy disabled by design. |
| **Contestants** | ✅ GET /admin/events/{e}/contestants | ✅ POST | ✅ GET .../contestants/{c} | ✅ PUT | ✅ DELETE (soft) | Nested under event. Delete blocked if approved scores exist. |
| **Categories** | ✅ GET /organizer/categories?event_id= | ✅ POST /organizer/categories | ✅ GET .../categories/{id} | ✅ PUT | ✅ DELETE (soft) | Weight sum ≤ 100%. Delete blocked if criteria have submitted scores. |
| **Criteria**  | ✅ GET /organizer/categories/{c}/criteria | ✅ POST .../criteria | ✅ GET .../criteria/{id} | ✅ PUT | ✅ DELETE (soft) | Nested under category. Delete blocked if any scores exist. |
| **Scores**    | — | ✅ POST /judge/scores (upsert draft) | — | ✅ PUT /judge/scores/{id} | — | Judge only; submit via POST .../events/{e}/scores/submit. Approve via admin. |

**Other endpoints (not classic CRUD):**  
Publish results, Unlock judge scoring, Score review (list/approve), MC results/reveal, Progress, Auth login/logout/me.

---

## 3. Frontend ↔ API Wiring

| Page / Flow           | List | Create | Update | Delete | Status |
|-----------------------|------|--------|--------|--------|--------|
| **Admin → Users**     | ✅ fetch /api/v1/admin/users | ✅ modal → POST | ✅ modal → PUT | ✅ DecisionModal → DELETE | **Fully wired** |
| **Admin → Event Setup** | N/A (event from server props) | ❌ No UI | ✅ form → PUT /admin/events/{id} | N/A | **Update only**; create event via seed/API. |
| **Admin → Contestants** | ✅ fetch .../events/{e}/contestants | ✅ modal → POST | ✅ modal → PUT | ✅ DecisionModal → DELETE | **Fully wired** |
| **Organizer → Categories** | ✅ fetch .../categories?event_id= | ❌ No UI | ❌ No UI | ❌ No UI | **Read-only** in UI. |
| **Organizer → Criteria** | ✅ fetch .../categories/{c}/criteria | ❌ No UI | ❌ No UI | ❌ No UI | **Read-only** in UI. |
| **Judge → Scoresheet** | ✅ fetch .../scoresheet | ✅ store (per cell) | N/A (upsert in store) | N/A | **Create/Update** via score inputs + Submit all. |

**Conclusion:**  
- **Users** and **Contestants** have full CRUD in the UI.  
- **Event** has Update only in the UI (no create event form).  
- **Categories** and **Criteria** have backend CRUD but **no create/edit/delete UI** (display only).  
- **Scores** are create/update (draft) + submit by judge; approve by admin (no resource delete).

---

## 4. Backend Verification (Code Paths)

- **UserController:** index (with optional role filter), store (validation, hash password, created_by), show, update (guard self), destroy (guard self, soft delete). ✅  
- **EventController:** index, store, show, update, destroy (403), publish, unlockScoring. ✅  
- **ContestantController:** index/store/show/update/destroy scoped to event; destroy checks approved scores. ✅  
- **CategoryController:** index (event_id query or latest event), store (weight ≤ 100%), show, update, destroy (checks criteria/scores). ✅  
- **CriterionController:** index/store/show/update/destroy scoped to category; destroy checks scores. ✅  
- **ScoreController:** scoresheet (read), store (upsert draft), update (draft only), submitAll. ✅  

All use the base `Controller::respond()` / `Controller::error()` JSON envelope. Validation and business rules (weight cap, delete guards) are implemented.

---

## 5. Manual CRUD Test Checklist

Run with a **Super Admin** account (e.g. `superadmin@tabulation.com` / `admin123` after `php artisan migrate:fresh --seed`).

### Users (Admin)
- [ ] **List:** Open `/admin/users` → table shows seeded users.
- [ ] **Create:** Click “Create User” → fill form → submit → new user in table, toast success.
- [ ] **Update:** Click “Edit” on a user → change name → submit → row updates, toast success.
- [ ] **Delete:** Click “Delete” → confirm in modal → user removed from table, toast success.
- [ ] **Guard:** Try deleting your own user → expect error message.

### Event (Admin)
- [ ] **Update:** Open `/admin/event` → change name/venue/date → Save → success toast and updated data.

### Contestants (Admin)
- [ ] **List:** Open `/admin/contestants` (with event selected) → table shows contestants.
- [ ] **Create:** “Add contestant” → number, name, etc. → submit → new row.
- [ ] **Update:** Edit contestant → save → row updates.
- [ ] **Delete:** Delete contestant (with no approved scores) → row removed.

### Categories & Criteria (Organizer)
- [ ] **List only:** `/organizer/categories` and `/organizer/criteria` show data; no create/edit/delete buttons (by design in current UI).

### Scores (Judge)
- [ ] **Read:** `/judge/scoresheet` shows matrix.
- [ ] **Create/Update:** Enter scores in cells → “Save draft” or blur → draft saved.
- [ ] **Submit:** “Submit All Scores” → confirm → status changes, toast success.

---

## 6. Automated Tests

The existing PHPUnit suite is configured to use **SQLite in-memory** (`phpunit.xml`). If your environment does not have the PHP SQLite driver (e.g. some Windows/Laragon setups), tests will fail with “could not find driver”.

**To run tests:**
- Ensure PHP has `pdo_sqlite` (and `sqlite3` if required), or  
- Point tests at MySQL by changing `phpunit.xml`: set `DB_CONNECTION` to `mysql` and ensure `DB_DATABASE` is a test database.

A dedicated **API CRUD test** is provided in `tests/Feature/Api/CrudOperationsTest.php`. It:
- Uses `RefreshDatabase` and assumes a working test DB (e.g. SQLite in-memory or MySQL test DB).
- Creates a super_admin user and exercises:
  - **Users:** list, create, update, delete (and guard: cannot delete self).
  - **Events:** list, create, update; destroy returns 403.
  - **Contestants:** list, create, update, delete (under an event).
- Run with: `php artisan test tests/Feature/Api/CrudOperationsTest.php`

---

## 7. Summary

| Area              | Status |
|-------------------|--------|
| Backend CRUD API  | ✅ Implemented and consistent for Users, Events, Contestants, Categories, Criteria; Scores are create/update/submit/approve. |
| Frontend Users    | ✅ Full CRUD. |
| Frontend Contestants | ✅ Full CRUD. |
| Frontend Event    | ✅ Update only (no create in UI). |
| Frontend Categories/Criteria | ⚠️ Read-only; backend supports full CRUD. |
| Frontend Scores   | ✅ Create/update (draft) + submit. |
| Business rules    | ✅ Weight cap, delete guards, role checks in place. |

**Recommendation:**  
- For **Categories** and **Criteria**, add create/edit/delete modals (and API calls) on the Organizer pages if you want full CRUD in the UI.  
- For **Events**, add a “Create event” flow (e.g. on Admin) if you need to create events from the app instead of seeds/API.
