# Phase 4 — Integration Testing

After both backend and frontend are complete, run through every check below. Fix any failure before considering the project done.

## Backend API Checks

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
    expect: 200, { data: { revealed: [], has_more: false } }  (empty — nothing revealed yet)

✓ POST /api/v1/mc/events/1/results/reveal  (MC token)
    expect: 200, { data: { result: { rank: 5, contestant_name: "...", final_score: "..." }, has_more } }

✓ GET /api/v1/admin/scores/review  (MC token — wrong role)
    expect: 403 Forbidden

✓ GET /api/v1/admin/users  (no token — checking 401)
    expect: 401 Unauthenticated
```

*(Note: Prompt references GET /api/v1/budgets for 401; this app has no budgets. Use GET /api/v1/admin/users without token instead.)*

## Frontend UI Checks

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

## Phase 5 — Final Checklist

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

## Notes

- **Session auth:** If Inertia pages that call the API get **401**, ensure `config/sanctum.php` `stateful` includes your app URL (e.g. `127.0.0.1:8000`) and that fetch uses `credentials: 'include'`. `EnsureFrontendRequestsAreStateful` is prepended to the API middleware in `bootstrap/app.php` for same-origin session auth.
- **Token tests:** Use `POST /api/v1/auth/login` with `email` and `password`, then send `Authorization: Bearer <token>` on subsequent requests.
- **MC event-scoped routes:** Phase 4 checklist uses `GET /api/v1/mc/events/1/results` and `POST /api/v1/mc/events/1/results/reveal`; the app also supports `GET /api/v1/mc/results` and `POST /api/v1/mc/results/reveal` (latest event) for the Reveal page.
