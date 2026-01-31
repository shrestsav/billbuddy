# BillBuddy

A full-stack expense sharing application inspired by Splitwise. Track shared expenses, split bills with friends and groups, and simplify debt settlements.

**Live Demo:** https://billbuddy.utsav-mailvio.site

## Features

### User Management
- Email/password registration with verification
- Secure authentication with Laravel Sanctum tokens
- Profile management (name, avatar, timezone, currency preference)
- Password reset via email

### Friends System
- Add friends by email search
- Send, accept, or reject friend requests
- View friend list with outstanding balances
- Track personal expenses with non-group friends

### Groups
- Create groups for different purposes (home, trips, couples, events)
- Add and remove members with role management (admin/member)
- Group-specific expense tracking
- Automatic debt simplification option

### Expense Management
- **4 Split Types:**
  - Equal: Split evenly among participants
  - Percentage: Custom percentages per person
  - Shares: Divide by share units (e.g., 2:1:1)
  - Exact: Specify exact amounts per person
- 15 expense categories (Food, Transport, Entertainment, etc.)
- Receipt image upload
- Recurring expenses (daily, weekly, monthly, yearly)
- Date selection and notes

### Settlements & Balances
- View who owes whom
- Record payments and settlements
- Debt simplification algorithm (minimizes number of transactions)
- Multi-currency support (40+ currencies)
- Exchange rate conversion

### Analytics
- Spending by category (pie/donut charts)
- Spending over time (line/bar charts)
- Monthly comparisons
- Group spending summaries

### Activity Feed
- Real-time activity log
- Tracks expense additions, edits, and deletions
- Settlement notifications
- Group member changes

## Tech Stack

### Backend
- **Framework:** Laravel 12 (PHP 8.2+)
- **Authentication:** Laravel Sanctum
- **Database:** MySQL 8.0
- **Cache/Queue:** Redis

### Frontend
- **Framework:** Vue.js 3.5+ with TypeScript
- **Build Tool:** Vite 7
- **State Management:** Pinia
- **Routing:** Vue Router
- **Styling:** Tailwind CSS 4
- **UI Components:** HeadlessUI, Heroicons
- **Charts:** Chart.js with vue-chartjs

### Infrastructure
- **Web Server:** Nginx
- **Containerization:** Docker & Docker Compose
- **CI/CD:** GitHub Actions

## Prerequisites

- Docker and Docker Compose (recommended)
- Or for standalone development:
  - PHP 8.2+
  - Composer
  - Node.js 20+
  - MySQL 8.0
  - Redis

## Installation

### Quick Start with Docker

```bash
# Clone the repository
git clone https://github.com/yourusername/billbuddy.git
cd billbuddy

# Start containers
docker-compose up -d

# Install backend dependencies
docker exec billbuddy-php composer install

# Copy environment file and generate key
docker exec billbuddy-php cp .env.example .env
docker exec billbuddy-php php artisan key:generate

# Run migrations and seeders
docker exec billbuddy-php php artisan migrate --seed

# Install frontend dependencies and build
docker exec billbuddy-node npm install
docker exec billbuddy-node npm run build

# Add to /etc/hosts (Linux/Mac)
echo "127.0.0.1 billbuddy.local api.billbuddy.local" | sudo tee -a /etc/hosts
```

Access the application:
- **Frontend:** http://billbuddy.local
- **API:** http://api.billbuddy.local

### Standalone Development

**Backend:**
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Configure your database in .env, then:
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

**Frontend:**
```bash
cd frontend
npm install
npm run dev
```

## Project Structure

```
billbuddy/
├── backend/                    # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/Api/   # API controllers
│   │   ├── Models/                 # Eloquent models
│   │   └── Services/               # Business logic
│   ├── database/
│   │   ├── migrations/             # Database migrations
│   │   └── seeders/                # Data seeders
│   └── routes/api.php              # API routes
│
├── frontend/                   # Vue.js SPA
│   ├── src/
│   │   ├── api/                    # API client modules
│   │   ├── stores/                 # Pinia state stores
│   │   ├── views/                  # Page components
│   │   ├── components/             # Reusable components
│   │   ├── router/                 # Vue Router config
│   │   └── types/                  # TypeScript definitions
│   └── dist/                       # Production build
│
├── docker/                     # Docker configuration
│   ├── php/Dockerfile
│   └── nginx/default.conf
│
└── docker-compose.yml          # Container orchestration
```

## API Overview

The API provides 47 endpoints organized into these categories:

| Category | Endpoints | Description |
|----------|-----------|-------------|
| Authentication | 9 | Register, login, logout, password reset |
| Users | 4 | Profile, settings, avatar, search |
| Friends | 6 | Friend requests, management |
| Groups | 11 | CRUD, members, balances |
| Expenses | 6 | CRUD, receipt upload |
| Settlements | 5 | Payments, balances |
| Analytics | 4 | Spending insights |
| Activity | 1 | Activity feed |

For detailed API documentation, see [DOCUMENTATION.md](./DOCUMENTATION.md).

## Docker Services

| Service | Port | Description |
|---------|------|-------------|
| Nginx | 80 | Web server |
| PHP-FPM | 9000 | Laravel backend |
| MySQL | 3306 | Database |
| Redis | 6379 | Cache/Queue |
| Node | 5173 | Frontend dev server |
| Mailpit | 8025 | Email testing |

## Development Commands

```bash
# Run tests
docker exec billbuddy-php php artisan test

# Code style (Laravel Pint)
docker exec billbuddy-php ./vendor/bin/pint

# Fresh database with seeds
docker exec billbuddy-php php artisan migrate:fresh --seed

# Cache configuration (production)
docker exec billbuddy-php php artisan config:cache
docker exec billbuddy-php php artisan route:cache
docker exec billbuddy-php php artisan view:cache

# Frontend development
docker exec billbuddy-node npm run dev

# Frontend production build
docker exec billbuddy-node npm run build

# View logs
docker logs billbuddy-php
docker logs billbuddy-nginx
```

## Environment Variables

Key backend environment variables (`.env`):

```env
APP_NAME=BillBuddy
APP_ENV=local
APP_DEBUG=true
APP_URL=http://api.billbuddy.local

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=billbuddy
DB_USERNAME=billbuddy
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379

FRONTEND_URL=http://billbuddy.local
```

## Deployment

The project includes GitHub Actions for automated deployment:

1. Push changes to the main branch
2. GitHub Actions triggers the deploy workflow
3. The deploy script pulls latest code, installs dependencies, runs migrations, and builds the frontend

Server requirements:
- PHP 8.2+
- Node.js 20+
- MySQL 8.0
- Redis
- Nginx
- Git

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open-sourced software.
