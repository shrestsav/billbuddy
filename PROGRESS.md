# BillBuddy - Development Progress Tracker

> **Project:** BillBuddy - A full clone of [Splitwise](https://www.splitwise.com/)
> **Purpose:** This file tracks implementation progress across Claude Code sessions.
> **Read this file at the start of each new session to understand the current state and continue from where we left off.

## WHAT IS THIS PROJECT?

**BillBuddy is a complete clone of [https://www.splitwise.com/](https://www.splitwise.com/)** - the popular expense-splitting app.

Splitwise helps users:
- Track shared expenses with friends, roommates, and groups
- Split bills equally or by custom amounts/percentages
- Keep track of who owes whom
- Settle debts and record payments
- Support multiple currencies
- Categorize expenses and view spending analytics

---

## Current Status Summary

| Component | Status | Progress |
|-----------|--------|----------|
| Project Structure | ✅ Complete | 100% |
| Docker Config | ✅ Complete | 100% |
| Laravel Installation | ✅ Complete | 100% |
| Database Migrations | ✅ Complete | 100% |
| Eloquent Models | ✅ Complete | 100% |
| Seeders | ✅ Complete | 100% |
| Authentication API | ✅ Complete | 100% |
| Groups API | ✅ Complete | 100% |
| Expenses API | ✅ Complete | 100% |
| Settlements API | ✅ Complete | 100% |
| Services (Balance, etc.) | ✅ Complete | 100% |
| Vue.js Frontend | ✅ Complete | 100% |
| Nginx Configuration | ✅ Complete | 100% |

**Last Updated:** 2026-01-29
**Status:** DEPLOYED - API JSON parsing issue being debugged

---

## PRODUCTION DEPLOYMENT (2026-01-29)

### Live URL
- **Frontend:** https://billbuddy.utsav-mailvio.site
- **API:** https://billbuddy.utsav-mailvio.site/api/

### DNS Configuration (Namecheap)
- A Record: `billbuddy` → `195.201.146.94`

### What's Working
- ✅ Frontend loads correctly (Vue.js SPA)
- ✅ SSL certificate installed (Let's Encrypt)
- ✅ Nginx configuration for frontend and API routing
- ✅ Laravel .env updated for production URLs
- ✅ API routes respond (with Accept: application/json header)
- ✅ Registration works via PHP CLI (tested with artisan tinker)
- ✅ Sanctum personal_access_tokens migration completed
- ✅ Mail configured to use `log` driver (emails logged, not sent)

### CURRENT ISSUE - JSON Body Not Parsed in POST Requests

**Problem:** When making POST requests via curl/browser to API endpoints, the JSON request body is not being parsed by Laravel. The request validates with "field required" errors even when data is sent.

**Symptoms:**
- `$request->all()` returns empty array `[]`
- `$request->getContent()` returns the raw JSON string correctly
- Simple closure routes that just return `$request->all()` work fine
- Controller-based routes fail validation

**What's Been Tried:**
1. Removed stateful API middleware (CSRF was causing issues initially)
2. Updated nginx fastcgi params to include `fastcgi_pass_request_body on`
3. Restarted PHP-FPM multiple times
4. Cleared OPcache via CLI
5. Ran `composer dump-autoload`
6. Renamed controller methods
7. Created new TestController - same issue

**Files Modified for Debugging:**
- `/var/www/billbuddy/backend/app/Http/Controllers/Api/AuthController.php` - Changed to use `Request` instead of `RegisterRequest`, renamed method to `createAccount`
- `/var/www/billbuddy/backend/app/Http/Controllers/Api/TestController.php` - New controller with register method
- `/var/www/billbuddy/backend/routes/api.php` - Multiple debug routes added

**Likely Cause:** PHP-FPM OPcache aggressively caching old code. The cache persists even after restarts.

**To Fix - Try These:**
1. Set `opcache.revalidate_freq=0` in PHP-FPM config and restart
2. Or disable OPcache temporarily: `opcache.enable=0` in `/etc/php/8.5/fpm/php.ini`
3. Or clear OPcache via web request (need to access clear-cache.php through nginx)

**Test Commands:**
```bash
# This should work (returns JSON with request data)
curl -s https://billbuddy.utsav-mailvio.site/api/direct-signup -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{"name":"Test"}'

# Registration test
curl -s https://billbuddy.utsav-mailvio.site/api/auth/register -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{"name":"Test","email":"test@example.com","password":"Password123!","password_confirmation":"Password123!"}'
```

### Production Config Files
- **Nginx:** `/etc/nginx/sites-enabled/billbuddy` (symlinked from `/var/www/billbuddy/nginx-production.conf`)
- **Laravel .env:** Updated with production URLs and `MAIL_MAILER=log`

---

## QUICK START

### To run the application:

1. **Add hosts entry:**
```bash
echo "127.0.0.1 billbuddy.local api.billbuddy.local" | sudo tee -a /etc/hosts
```

2. **Configure Nginx:**
```bash
sudo cp /var/www/billbuddy/nginx-billbuddy.conf /etc/nginx/sites-available/billbuddy
sudo ln -s /etc/nginx/sites-available/billbuddy /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

3. **Access the app:**
- Frontend: http://billbuddy.local
- API: http://api.billbuddy.local

### For development:
```bash
# Backend (Laravel)
cd /var/www/billbuddy/backend
php artisan serve --host=0.0.0.0 --port=8000

# Frontend (Vue.js with hot reload)
cd /var/www/billbuddy/frontend
npm run dev
```

---

## PROJECT OVERVIEW

**Project Name:** BillBuddy
**Clone Of:** [https://www.splitwise.com/](https://www.splitwise.com/) (Expense Splitting App)
**Location:** `/var/www/billbuddy/`

**Tech Stack:**
- Backend: Laravel 12.49.0 (PHP 8.5.1)
- Frontend: Vue.js 3.5 + TypeScript + Pinia + Tailwind CSS 4
- Database: MySQL 8.0
- Web Server: Nginx 1.24.0
- Node.js: v24.13.0

**Directory Structure:**
```
/var/www/billbuddy/
├── backend/              # Laravel 12 API ✅
│   ├── app/
│   │   ├── Http/Controllers/Api/    # All API controllers
│   │   ├── Models/                   # Eloquent models
│   │   └── Services/                 # Business logic services
│   ├── database/
│   │   ├── migrations/               # All migrations
│   │   └── seeders/                  # Category & Currency seeders
│   └── routes/api.php                # API routes (47 endpoints)
├── frontend/             # Vue.js 3 SPA ✅
│   ├── src/
│   │   ├── api/                      # API client modules
│   │   ├── components/               # Vue components
│   │   ├── stores/                   # Pinia stores
│   │   ├── types/                    # TypeScript types
│   │   └── views/                    # Page components
│   └── dist/                         # Production build
├── nginx-billbuddy.conf  # Nginx config ✅
├── DOCUMENTATION.md      # Full feature documentation ✅
└── PROGRESS.md           # This file ✅
```

---

## COMPLETED WORK

### Session 2 (2026-01-28) - FULL BUILD COMPLETE

#### 1. Database Migrations ✅ RUN
```bash
php artisan migrate --force
php artisan db:seed
```
- All 12 migrations ran successfully
- Categories (15) and Currencies (40) seeded

#### 2. Authentication System ✅
**Files created:**
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Requests/Auth/RegisterRequest.php`
- `app/Http/Requests/Auth/LoginRequest.php`

**Endpoints:**
- POST `/api/auth/register` - User registration
- POST `/api/auth/login` - Login with token
- POST `/api/auth/logout` - Logout current device
- POST `/api/auth/logout-all` - Logout all devices
- GET `/api/auth/user` - Get current user
- POST `/api/auth/forgot-password` - Password reset request
- POST `/api/auth/reset-password` - Reset password
- GET `/api/auth/verify-email/{id}/{hash}` - Email verification
- POST `/api/auth/resend-verification` - Resend verification

#### 3. API Controllers ✅
All controllers created in `app/Http/Controllers/Api/`:

| Controller | Endpoints | Features |
|------------|-----------|----------|
| UserController | 4 | Search, update profile/settings, avatar upload |
| FriendController | 6 | List, pending, invite, accept, reject, remove |
| GroupController | 11 | CRUD, members, balances, expenses, settlements |
| ExpenseController | 6 | CRUD with 4 split types, receipt upload |
| SettlementController | 5 | Create settlements, view balances, simplified debts |
| CategoryController | 1 | List all categories |
| AnalyticsController | 4 | Category spending, time series, group summary, monthly |
| ActivityController | 1 | Activity feed with filters |

**Total: 47 API endpoints**

#### 4. Services ✅
Created in `app/Services/`:

| Service | Purpose |
|---------|---------|
| BalanceCalculatorService | Calculate who owes whom between users |
| DebtSimplifierService | Minimize transactions using greedy algorithm |
| CurrencyConverterService | Multi-currency conversion and formatting |

#### 5. Vue.js Frontend ✅
**Setup:**
- Vue 3.5 + TypeScript + Pinia + Vue Router
- Tailwind CSS 4 with @tailwindcss/vite plugin
- HeadlessUI + HeroIcons
- Chart.js + vue-chartjs for analytics
- date-fns for date formatting
- axios for API calls

**API Layer** (`src/api/`):
- `client.ts` - Axios instance with auth interceptor
- `auth.ts`, `groups.ts`, `expenses.ts`, `friends.ts`
- `settlements.ts`, `analytics.ts`, `categories.ts`, `users.ts`, `activity.ts`

**Stores** (`src/stores/`):
- `auth.ts` - Authentication state and methods
- `groups.ts` - Groups management
- `expenses.ts` - Expenses with pagination
- `friends.ts` - Friends and pending requests
- `categories.ts` - Categories cache

**Views** (`src/views/`):
- `auth/` - LoginView, RegisterView
- `dashboard/` - DashboardView (balances, groups, activity)
- `groups/` - GroupsListView, GroupCreateView, GroupDetailView
- `friends/` - FriendsView (list, requests, invite modal)
- `expenses/` - ExpensesListView, ExpenseCreateView
- `settlements/` - SettlementsView (debts, credits, settle modal)
- `analytics/` - AnalyticsView (pie chart, bar chart, breakdown)
- `activity/` - ActivityView
- `settings/` - SettingsView (profile, preferences)

**Components** (`src/components/`):
- `layout/` - AppLayout, Navbar, Sidebar

**Router:**
- Auth guards (requiresAuth, guest)
- 14 routes configured

#### 6. Nginx Configuration ✅
Created `nginx-billbuddy.conf`:
- API server on api.billbuddy.local
- Frontend server on billbuddy.local
- API proxy for `/api` routes
- Static asset caching
- CORS headers

---

## DATABASE SCHEMA

### Tables Created:
1. `users` - User accounts with avatar, currency, timezone
2. `password_reset_tokens` - Password reset tokens
3. `sessions` - User sessions
4. `cache` - Laravel cache
5. `jobs` - Queue jobs
6. `categories` - 15 expense categories
7. `currencies` - 40 world currencies with exchange rates
8. `groups` - Expense groups (home, trip, couple, other)
9. `group_members` - Group membership with roles
10. `friends` - Friend relationships (pending/accepted)
11. `expenses` - Expenses with 4 split types
12. `expense_splits` - Individual expense splits
13. `settlements` - Payment settlements
14. `activity_logs` - Activity tracking

---

## FEATURES IMPLEMENTED

### Authentication
- Registration with email verification
- Login with Sanctum token authentication
- Password reset flow
- Profile and settings management

### Friends
- Search users by email
- Send/accept/reject friend requests
- View pending requests (sent/received)
- Remove friends

### Groups
- Create groups with type (home, trip, couple, other)
- Add/remove members
- Admin role management
- View group balances, expenses, settlements

### Expenses
- 4 split types: equal, percentage, shares, exact
- Category selection (15 categories)
- Date picker
- Notes and receipt upload
- Filter by group, category, date range

### Settlements
- View simplified debts (who owes whom)
- Record settlements/payments
- View settlement history

### Analytics
- Spending by category (pie chart)
- Spending over time (bar chart - daily/weekly/monthly)
- Category breakdown with percentages
- Monthly summary

### Activity
- Activity feed across all groups
- Action icons by type
- Time-relative formatting

---

## API ENDPOINTS REFERENCE

### Authentication (9)
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
POST   /api/auth/logout-all
GET    /api/auth/user
POST   /api/auth/forgot-password
POST   /api/auth/reset-password
GET    /api/auth/verify-email/{id}/{hash}
POST   /api/auth/resend-verification
```

### Users (4)
```
GET    /api/users/search?email=
PUT    /api/users/profile
PUT    /api/users/settings
POST   /api/users/avatar
```

### Friends (6)
```
GET    /api/friends
GET    /api/friends/pending
POST   /api/friends/invite
PUT    /api/friends/{id}/accept
PUT    /api/friends/{id}/reject
DELETE /api/friends/{id}
```

### Groups (11)
```
GET    /api/groups
POST   /api/groups
GET    /api/groups/{id}
PUT    /api/groups/{id}
DELETE /api/groups/{id}
POST   /api/groups/{id}/members
DELETE /api/groups/{id}/members/{userId}
PUT    /api/groups/{id}/members/{userId}/role
GET    /api/groups/{id}/balances
GET    /api/groups/{id}/expenses
GET    /api/groups/{id}/settlements
```

### Expenses (6)
```
GET    /api/expenses
POST   /api/expenses
GET    /api/expenses/{id}
PUT    /api/expenses/{id}
DELETE /api/expenses/{id}
POST   /api/expenses/{id}/receipt
```

### Settlements (5)
```
GET    /api/settlements
POST   /api/settlements
GET    /api/settlements/{id}
GET    /api/balances
GET    /api/balances/simplified
```

### Other (6)
```
GET    /api/categories
GET    /api/analytics/spending-by-category
GET    /api/analytics/spending-over-time
GET    /api/analytics/group-summary/{id}
GET    /api/analytics/monthly-summary
GET    /api/activity
```

---

## NEXT STEPS (Optional Enhancements)

1. **Testing**
   - PHPUnit tests for API endpoints
   - Vitest/Cypress tests for frontend

2. **Features**
   - Email notifications
   - Recurring expenses automation
   - Receipt OCR
   - Export to CSV/PDF
   - Push notifications

3. **Performance**
   - Redis caching
   - Queue workers for emails
   - Database indexes optimization

4. **Security**
   - Rate limiting
   - Input sanitization audit
   - Security headers

---

## SYSTEM INFORMATION

- **OS:** Ubuntu (Linux 6.8.0-90-generic)
- **PHP:** 8.5.1
- **Node.js:** v24.13.0
- **Nginx:** 1.24.0 (running)
- **MySQL:** 8.0.44 (running)
- **Composer:** 2.9.4
- **User:** utsav
- **Working Directory:** /var/www/billbuddy

**MySQL Database:**
- Database: `billbuddy`
- User: `billbuddy`
- Password: `secret`

---

## SESSION LOG

### Session 1 (2026-01-28)
- Created project structure
- Created DOCUMENTATION.md
- Set up Docker config files
- Installed Laravel 12.49.0
- Installed Laravel Sanctum
- Configured .env for MySQL
- Created all 9 custom migrations
- Created all 10 Eloquent models with relationships
- Created CategorySeeder (15 categories)
- Created CurrencySeeder (40 currencies)
- Updated DatabaseSeeder
- **Blocked:** PHP mbstring extension

### Session 2 (2026-01-28) - COMPLETED
- Ran all migrations successfully
- Seeded database with categories and currencies
- Created AuthController with full auth flow
- Created RegisterRequest and LoginRequest
- Created API routes (47 endpoints)
- Created all 8 API controllers
- Created 3 service classes (Balance, Debt, Currency)
- Initialized Vue.js frontend with TypeScript
- Installed Tailwind CSS 4, HeadlessUI, Chart.js
- Created API client layer with axios
- Created 5 Pinia stores
- Created AppLayout with Navbar and Sidebar
- Created all views (auth, dashboard, groups, friends, expenses, settlements, analytics, activity, settings)
- Built production frontend
- Created Nginx configuration
- **Status:** PROJECT COMPLETE

---

**END OF PROGRESS FILE**

