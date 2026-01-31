# BillBuddy - Expense Splitting Application

**A full-featured clone of [Splitwise](https://www.splitwise.com/)** built with Laravel (backend) and Vue.js (frontend).

> Reference: [https://www.splitwise.com/](https://www.splitwise.com/)

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Tech Stack](#tech-stack)
3. [Project Structure](#project-structure)
4. [Features](#features)
5. [Installation & Setup](#installation--setup)
6. [API Documentation](#api-documentation)
7. [Database Schema](#database-schema)
8. [Key Algorithms](#key-algorithms)

---

## Project Overview

BillBuddy helps users track shared expenses and settle debts with friends, family, or roommates. It supports multiple split types, group management, multi-currency transactions, recurring expenses, and spending analytics.

---

## Tech Stack

| Component | Technology |
|-----------|------------|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend | Vue.js 3 + TypeScript |
| Database | MySQL 8.0 |
| Cache/Queue | Redis |
| Web Server | Nginx |
| Containerization | Docker & Docker Compose |
| Authentication | Laravel Sanctum (Token-based) |
| State Management | Pinia |
| Styling | Tailwind CSS |
| Charts | Chart.js |

---

## Project Structure

```
/var/www/billbuddy/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   └── Requests/
│   │   ├── Models/
│   │   └── Services/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/api.php
├── frontend/                # Vue.js SPA
│   ├── src/
│   │   ├── views/
│   │   ├── components/
│   │   ├── stores/
│   │   ├── services/
│   │   └── router/
│   └── package.json
├── docker/                  # Docker configuration
│   ├── nginx/default.conf
│   └── php/Dockerfile
├── docker-compose.yml
└── DOCUMENTATION.md
```

---

## Features

### Core Features

#### 1. User Management
- Email/password registration with verification
- Profile management (name, avatar, timezone)
- Currency preference setting
- Password reset functionality

#### 2. Friends System
- Add friends by email
- Accept/reject friend requests
- View friend list with balances
- Track expenses with non-group friends

#### 3. Groups
- Create groups for trips, roommates, events
- Add/remove group members
- Assign admin roles
- Group-specific expense tracking
- Enable/disable debt simplification per group

#### 4. Expenses
- Add expenses with description, amount, date
- Assign payer (who paid)
- Choose participants (who owes)
- Multiple split types:
  - **Equal**: Split evenly among all participants
  - **Percentage**: Each person pays a percentage
  - **Shares**: Divide by share units (e.g., 2:1:1)
  - **Exact**: Specify exact amounts per person
- Attach receipt images
- Categorize expenses (Food, Transport, etc.)
- Add notes

#### 5. Recurring Expenses
- Set expenses to repeat automatically
- Frequencies: Daily, Weekly, Monthly, Yearly
- Automatic expense creation via scheduler

#### 6. Settlements
- View balances (who owes whom)
- Record payments/settlements
- Multiple payment methods tracking
- Debt simplification algorithm

#### 7. Multi-Currency Support
- 100+ currencies supported
- Set default currency per user
- Currency conversion for settlements
- Real-time exchange rates (optional)

#### 8. Categories
Pre-defined expense categories:
- Food & Drink
- Transportation
- Entertainment
- Utilities
- Rent/Mortgage
- Groceries
- Shopping
- Travel
- Healthcare
- Education
- Other

#### 9. Analytics & Charts
- Spending by category (pie/donut chart)
- Spending over time (line chart)
- Monthly comparisons
- Group spending summaries
- Export capabilities

#### 10. Activity Feed
- Real-time activity log
- Track all expense additions/edits
- Settlement notifications
- Group member changes

---

## Installation & Setup

### Prerequisites
- Docker & Docker Compose
- Git

### Quick Start

1. **Clone/Navigate to project:**
   ```bash
   cd /var/www/billbuddy
   ```

2. **Start Docker containers:**
   ```bash
   docker-compose up -d
   ```

3. **Install backend dependencies:**
   ```bash
   docker exec billbuddy-php composer install
   ```

4. **Run migrations and seeders:**
   ```bash
   docker exec billbuddy-php php artisan migrate --seed
   ```

5. **Install frontend dependencies:**
   ```bash
   docker exec billbuddy-node npm install
   ```

6. **Build frontend (or run dev server):**
   ```bash
   docker exec billbuddy-node npm run build
   # OR for development:
   docker exec billbuddy-node npm run dev
   ```

7. **Add to /etc/hosts:**
   ```
   127.0.0.1 billbuddy.local api.billbuddy.local
   ```

8. **Access the application:**
   - Frontend: http://billbuddy.local
   - API: http://api.billbuddy.local

### Environment Variables

Backend `.env` key variables:
```env
APP_NAME=BillBuddy
APP_URL=http://api.billbuddy.local
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=billbuddy
DB_USERNAME=billbuddy
DB_PASSWORD=secret
REDIS_HOST=redis
FRONTEND_URL=http://billbuddy.local
```

---

## API Documentation

### Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register new user |
| POST | `/api/auth/login` | Login user |
| POST | `/api/auth/logout` | Logout user |
| POST | `/api/auth/forgot-password` | Request password reset |
| POST | `/api/auth/reset-password` | Reset password |
| GET | `/api/auth/verify-email/{token}` | Verify email |
| GET | `/api/auth/user` | Get authenticated user |

### User Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/users/search?email=` | Search users by email |
| PUT | `/api/users/profile` | Update profile |
| PUT | `/api/users/settings` | Update settings |

### Friends Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/friends` | List all friends |
| POST | `/api/friends/invite` | Send friend request |
| PUT | `/api/friends/{id}/accept` | Accept friend request |
| DELETE | `/api/friends/{id}` | Remove friend |

### Groups Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/groups` | List all groups |
| POST | `/api/groups` | Create group |
| GET | `/api/groups/{id}` | Get group details |
| PUT | `/api/groups/{id}` | Update group |
| DELETE | `/api/groups/{id}` | Delete group |
| POST | `/api/groups/{id}/members` | Add member |
| DELETE | `/api/groups/{id}/members/{userId}` | Remove member |

### Expenses Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/expenses` | List expenses (with filters) |
| POST | `/api/expenses` | Create expense |
| GET | `/api/expenses/{id}` | Get expense details |
| PUT | `/api/expenses/{id}` | Update expense |
| DELETE | `/api/expenses/{id}` | Delete expense |
| POST | `/api/expenses/{id}/receipt` | Upload receipt |

### Settlements Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/settlements` | List settlements |
| POST | `/api/settlements` | Record settlement |
| GET | `/api/balances` | Get all balances |
| GET | `/api/balances/simplified` | Get simplified debts |

### Categories Endpoint

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/categories` | List all categories |

### Analytics Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/analytics/spending-by-category` | Category breakdown |
| GET | `/api/analytics/spending-over-time` | Time series data |
| GET | `/api/analytics/group-summary/{id}` | Group summary |

### Activity Endpoint

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/activity` | Get activity feed |

---

## Database Schema

### Tables Overview

```
users
├── id (primary)
├── name
├── email (unique)
├── password
├── avatar
├── currency_preference
├── timezone
├── email_verified_at
└── timestamps

groups
├── id (primary)
├── name
├── description
├── image
├── created_by (foreign → users)
├── simplify_debts (boolean)
└── timestamps

group_members
├── id (primary)
├── group_id (foreign → groups)
├── user_id (foreign → users)
├── role (enum: admin, member)
├── joined_at
└── timestamps

friends
├── id (primary)
├── user_id (foreign → users)
├── friend_id (foreign → users)
├── status (enum: pending, accepted)
└── timestamps

expenses
├── id (primary)
├── group_id (foreign → groups, nullable)
├── description
├── amount (decimal)
├── currency (string)
├── category_id (foreign → categories)
├── paid_by (foreign → users)
├── date
├── receipt_image
├── is_recurring (boolean)
├── recurring_frequency (enum)
├── notes
└── timestamps

expense_splits
├── id (primary)
├── expense_id (foreign → expenses)
├── user_id (foreign → users)
├── amount (decimal)
├── percentage (decimal, nullable)
├── shares (integer, nullable)
├── split_type (enum: equal, percentage, shares, exact)
└── timestamps

settlements
├── id (primary)
├── group_id (foreign → groups, nullable)
├── payer_id (foreign → users)
├── payee_id (foreign → users)
├── amount (decimal)
├── currency
├── payment_method
├── settled_at
├── notes
└── timestamps

categories
├── id (primary)
├── name
├── icon
├── color
└── timestamps

currencies
├── id (primary)
├── code (e.g., USD)
├── name
├── symbol
├── exchange_rate
└── timestamps

activity_logs
├── id (primary)
├── user_id (foreign → users)
├── action
├── subject_type
├── subject_id
├── metadata (json)
└── created_at
```

---

## Key Algorithms

### 1. Expense Splitting

```php
// Equal Split
$splitAmount = $totalAmount / count($participants);

// Percentage Split
$splitAmount = $totalAmount * ($userPercentage / 100);

// Shares Split
$splitAmount = $totalAmount * ($userShares / $totalShares);

// Exact Split
$splitAmount = $userSpecifiedAmount;
```

### 2. Balance Calculation

For each pair of users (A, B):
```
1. Sum expenses where A paid and B owes → A_to_B
2. Sum expenses where B paid and A owes → B_to_A
3. Sum settlements from A to B → Settled_A_to_B
4. Sum settlements from B to A → Settled_B_to_A

Net Balance = (A_to_B - Settled_A_to_B) - (B_to_A - Settled_B_to_A)

If positive: B owes A
If negative: A owes B
```

### 3. Debt Simplification Algorithm

Minimizes the number of transactions needed to settle all debts:

```
1. Calculate net balance for each user
   - Positive = Creditor (owed money)
   - Negative = Debtor (owes money)

2. Create two lists: creditors and debtors

3. While both lists have entries:
   a. Take largest creditor and largest debtor
   b. Transfer = min(creditor_balance, |debtor_balance|)
   c. Create settlement: debtor pays creditor the transfer amount
   d. Update balances
   e. Remove any user with zero balance

Result: Minimum number of transactions to settle all debts
```

### 4. Recurring Expense Processing

Daily scheduled task:
```
1. Query expenses where is_recurring = true
2. For each recurring expense:
   - Check if due based on frequency and last occurrence
   - If due: create new expense copy with updated date
   - Reset recurrence timer
```

---

## Development Notes

### Running Tests
```bash
docker exec billbuddy-php php artisan test
```

### Code Style
```bash
docker exec billbuddy-php ./vendor/bin/pint
```

### Database Refresh
```bash
docker exec billbuddy-php php artisan migrate:fresh --seed
```

### Viewing Logs
```bash
docker logs billbuddy-php
docker logs billbuddy-nginx
```

---

## License

This project is for educational purposes.
