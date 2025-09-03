# BIN Lookup System

A comprehensive Bank Identification Number (BIN) lookup system with Laravel API backend and Vue.js frontend, featuring UUID-based architecture, queue processing, and full Docker containerization.

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Quick Start](#quick-start)
- [Detailed Setup](#detailed-setup)
- [Environment Configuration](#environment-configuration)
- [System Architecture](#system-architecture)
- [API Documentation](#api-documentation)
- [Frontend Features](#frontend-features)
- [Data Processing Workflow](#data-processing-workflow)
- [Development Guide](#development-guide)
- [Troubleshooting](#troubleshooting)
- [Production Deployment](#production-deployment)

## Architecture Overview

### Technology Stack

**Backend (Laravel 11)**

- **Runtime**: PHP 8.2-FPM on Alpine Linux
- **Framework**: Laravel 11 with UUID-based models
- **Database**: MySQL 8.0 with optimized indexing
- **Cache/Queue**: Redis 7 for sessions, cache, and job queues
- **Web Server**: Nginx with PHP-FPM
- **Process Management**: Supervisor for background workers

**Frontend (Vue.js 3)**

- **Framework**: Vue.js 3 Composition API with TypeScript
- **Build Tool**: Vite with hot reload
- **Styling**: Tailwind CSS with responsive design
- **State Management**: Pinia stores for reactive data
- **HTTP Client**: Axios with interceptors

**Infrastructure**

- **Containerization**: Docker Compose multi-service architecture
- **Database Management**: phpMyAdmin for development
- **Queue Processing**: Dedicated worker container
- **Development Tools**: Hot reloading, volume mounts, centralized logging

### Design Decisions

1. **UUID Primary Keys**: All entities use UUIDs instead of auto-increment IDs for better scalability, security, and distributed system compatibility.

2. **Queue-Based Processing**: BIN lookups are processed asynchronously using Redis queues to handle external API rate limits and improve user experience.

3. **Repository Pattern**: Clean separation of data access logic with interfaces for better testability and maintainability.

4. **Resource-Based API**: Laravel API Resources ensure consistent JSON responses and data transformation.

5. **Docker-First Development**: Complete containerization eliminates "works on my machine" issues and simplifies deployment.

## Quick Start

### Directory Structure

The project has the following structure:
```
binlookup/
├── docker-compose.yml          # Main Docker Compose configuration
├── be/                        # Laravel Backend (PHP 8.2, Laravel 11)
│   ├── app/                   # Laravel application code
│   ├── database/              # Migrations, factories, seeders
│   ├── tests/                 # Pest test suite (80 tests)
│   └── ...
├── fe/                        # Vue.js Frontend (Vue 3, TypeScript, Tailwind)
│   ├── src/                   # Vue application code
│   ├── package.json           # Frontend dependencies
│   └── ...
└── docker/                    # Docker configuration files
    └── mysql/
        └── my.cnf
```

### Prerequisites

- Docker 20.10+ and Docker Compose 2.0+
- Git
- 4GB+ available RAM
- Ports 3306, 5173, 6379, 8000, 8080 available

### Installation

1. **Clone and Setup**:

   ```bash
   git clone https://github.com/emuroiwa/binlookup.git
   cd binlookup
   docker-compose up --build -d
   ```

2. **Access Applications**:
   - **Frontend**: http://localhost:5173
   - **API**: http://localhost:8000
   - **phpMyAdmin**: http://localhost:8080 (root/root_password)

## Detailed Setup

### Step-by-Step Installation

1. **Environment Setup**:

   ```bash
   # Clone repository
   git clone https://github.com/emuroiwa/binlookup.git
   cd binlookup

   # Environment configuration is already set up in Docker Compose
   # No additional .env file copying needed
   ```

2. **Build and Start Services**:

   ```bash
   # Build all containers and start services
   docker-compose up --build -d

   # Wait for services to start, then run migrations
   docker-compose exec laravel-api php artisan migrate
   ```

3. **Verify Installation**:

   ```bash
   # Check all services are running
   docker-compose ps

   # Test API endpoints
   curl http://localhost:8000/api/bin-imports

   # Access frontend
   open http://localhost:5173
   ```

### Available Commands

```bash
# Main Operations
docker-compose up --build -d    # Build and start all services
docker-compose up -d            # Start existing services
docker-compose down             # Stop all services
docker-compose restart          # Restart all services
docker-compose logs -f          # View logs from all services

# Development
docker-compose exec laravel-api bash    # Access Laravel container shell
docker-compose exec laravel-api php artisan migrate    # Run database migrations
docker-compose exec laravel-api php artisan migrate:fresh --seed    # Fresh database
docker-compose exec -T laravel-api ./vendor/bin/pest    # Run test suite

# Maintenance
docker-compose down -v          # Remove containers and volumes
docker-compose restart          # Restart all services

# Queue Management
docker-compose exec laravel-api php artisan queue:work redis --queue=bin-lookups
```

## Environment Configuration

### Environment Configuration

The system uses environment variables defined in `docker-compose.yml`. The Laravel backend configuration is handled automatically by Docker Compose:

```bash
# Application Configuration
APP_NAME="BIN Lookup System"
APP_ENV=local
APP_KEY=base64:vp13Cld/U9OKnbrke/FZHqG0qvnBzHh3v+REnym+Kb8=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Logging
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=mysql                    # Docker service name
DB_PORT=3306
DB_DATABASE=bin_lookup_system
DB_USERNAME=bin_user
DB_PASSWORD=secure_password      # Change in production

# Redis Configuration
REDIS_HOST=redis                 # Docker service name
REDIS_PASSWORD=redis_password    # Change in production
REDIS_PORT=6379

# Cache and Queue Configuration
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database
SESSION_LIFETIME=120

# External BIN API Configuration
BIN_API_BASE_URL=https://lookup.binlist.net
BIN_API_TIMEOUT=30
BIN_API_RATE_LIMIT_DELAY=1000   # Milliseconds between requests
```

### Production Environment Variables

For production deployment, modify these values:

```bash
APP_ENV=production
APP_DEBUG=false
DB_PASSWORD=<strong-password>
REDIS_PASSWORD=<strong-password>
LOG_LEVEL=warning
```

## System Architecture

### Database Schema

The system uses UUID-based entities with the following core tables:

**bin_imports**

- `id` (UUID, Primary Key)
- `filename` (VARCHAR) - Original CSV filename
- `total_bins` (INT) - Number of BINs in upload
- `processed_bins` (INT) - Successfully processed count
- `failed_bins` (INT) - Failed processing count
- `status` (ENUM) - pending, processing, completed, failed
- `started_at`, `completed_at` (TIMESTAMP)

**bin_lookups**

- `id` (UUID, Primary Key)
- `bin_import_id` (UUID, Foreign Key)
- `bin_number` (VARCHAR, 8) - The BIN number
- `status` (ENUM) - pending, completed, failed
- `attempts` (INT) - Retry count
- `error_message` (TEXT) - Failure details

**bin_data**

- `id` (UUID, Primary Key)
- `bin_lookup_id` (UUID, Foreign Key)
- `bin_number` (VARCHAR, 8)
- `bank_name`, `card_type`, `card_brand` (VARCHAR, Nullable)
- `country_code`, `country_name` (VARCHAR, Nullable)
- `website`, `phone` (VARCHAR, Nullable)
- `api_response` (JSON) - Raw API response

### Service Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Vue.js SPA    │    │   Laravel API   │    │     MySQL       │
│   (Port 5173)   │◄──►│   (Port 8000)   │◄──►│   (Port 3306)   │
│                 │    │                 │    │                 │
│ • File Upload   │    │ • REST API      │    │ • UUID Tables   │
│ • Data Display  │    │ • Queue Jobs    │    │ • Indexing      │
│ • Filtering     │    │ • BIN Processing│    │ • Relationships │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                │                        │
                                ▼                        │
                       ┌─────────────────┐               │
                       │     Redis       │               │
                       │   (Port 6379)   │               │
                       │                 │               │
                       │ • Job Queues    │               │
                       │ • Session Store │               │
                       │ • Cache Layer   │               │
                       └─────────────────┘               │
                                │                        │
                                ▼                        │
                       ┌─────────────────┐               │
                       │  Queue Worker   │               │
                       │                 │               │
                       │ • BIN API Calls │               │
                       │ • Data Storage  │◄──────────────┘
                       │ • Error Handling│
                       └─────────────────┘
```

### Request Flow

1. **CSV Upload**: User uploads CSV via frontend
2. **File Processing**: Laravel parses CSV and creates BinImport record
3. **Job Queuing**: Individual BinLookup jobs queued in Redis
4. **Background Processing**: Queue worker processes jobs asynchronously
5. **API Integration**: External BIN API calls with rate limiting
6. **Data Storage**: Results stored in bin_data table
7. **Status Updates**: Progress tracked and displayed to user

## API Documentation

### Authentication

Currently, the API is open (no authentication required). In production, implement:

- Laravel Sanctum for API tokens
- Rate limiting per IP/user
- CORS configuration for frontend domain

### Endpoints

**BIN Imports Management**

```http
GET /api/bin-imports
Content-Type: application/json

Response: Paginated list of imports with UUID identifiers
```

```http
POST /api/bin-imports
Content-Type: multipart/form-data

Body: file=@sample.csv
Response: Created import with processing status
```

```http
GET /api/bin-imports/{uuid}
Content-Type: application/json

Response: Import details with statistics
```

```http
DELETE /api/bin-imports/{uuid}
Content-Type: application/json

Response: Deletion confirmation
```

**BIN Data Access**

```http
GET /api/bin-data
Content-Type: application/json
Query Parameters:
  - bin: Filter by BIN number
  - bank: Filter by bank name
  - brand: Filter by card brand
  - type: Filter by card type
  - country: Filter by country
  - page: Page number
  - per_page: Results per page

Response: Paginated BIN data with metadata
```

```http
GET /api/bin-data/export
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet

Response: Excel file download
```

```http
GET /api/bin-data/filter-options
Content-Type: application/json

Response: Available filter values for dropdowns
```

### Response Format

All API responses follow a consistent structure:

```json
{
  "data": [...],
  "links": {
    "first": "http://localhost:8000/api/bin-data?page=1",
    "last": "http://localhost:8000/api/bin-data?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/bin-data?page=2"
  },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 67
  }
}
```

## Frontend Features

### BIN Imports Page

- **File Upload**: Drag-and-drop CSV upload with validation
- **Progress Tracking**: Real-time progress bars and status updates
- **Import History**: List of all imports with filtering and search
- **Batch Actions**: Delete multiple imports

### BIN Data Browser

- **Advanced Filtering**: Multi-column filters with autocomplete
- **Sorting**: Clickable column headers for sorting
- **Pagination**: Efficient pagination with page size options
- **Export**: Download filtered results as Excel/CSV
- **Statistics**: Summary cards showing totals and breakdowns

### Technical Features

- **Responsive Design**: Mobile-friendly interface
- **Error Handling**: User-friendly error messages and retry mechanisms
- **Loading States**: Skeleton screens and progress indicators
- **Real-time Updates**: Auto-refresh for processing status

## Data Processing Workflow

### CSV Upload Process

1. **File Validation**:

   - File type check (CSV only)
   - Size limits (configurable)
   - Header validation

2. **Data Parsing**:

   - CSV parsing with error handling
   - BIN number validation (6-8 digits)
   - Duplicate detection within file

3. **Record Creation**:

   - BinImport record with UUID
   - Individual BinLookup records with UUIDs
   - Atomic transaction for consistency

4. **Queue Processing**:

   - Jobs dispatched to `bin-lookups` queue
   - Rate limiting respects API limits
   - Exponential backoff for failures
   - Maximum retry attempts

5. **External API Integration**:

   - HTTP client with timeout configuration
   - Response caching for duplicate BINs
   - Error classification (temporary vs permanent)

6. **Data Enrichment**:

   - API response parsing and validation
   - Data normalization (country codes, card types)
   - Storage in structured format

7. **Status Tracking**:
   - Real-time progress updates
   - Error logging and reporting
   - Completion notifications

### Queue Worker Configuration

The queue worker processes jobs with these settings:

```bash
# Command executed by queue worker container
php artisan queue:work redis --queue=bin-lookups --sleep=3 --tries=3 --timeout=120

# Configuration:
# --queue=bin-lookups: Process specific queue
# --sleep=3: Wait 3 seconds between jobs
# --tries=3: Maximum retry attempts
# --timeout=120: Job timeout in seconds
```

## Development Guide

### Local Development Setup

1. **Container Access**:

   ```bash
   # Laravel container shell
   docker-compose exec laravel-api bash

   # View logs
   docker-compose logs -f laravel-api
   docker-compose logs -f vue-frontend
   docker-compose logs -f queue-worker
   ```

2. **Database Management**:

   ```bash
   # Run migrations
   docker-compose exec laravel-api php artisan migrate

   # Fresh database with seeders
   docker-compose exec laravel-api php artisan migrate:fresh --seed

   # Access database via phpMyAdmin
   # URL: http://localhost:8080
   # User: root
   # Password: root_password
   ```

3. **Queue Debugging**:

   ```bash
   # Manual queue processing
   docker-compose exec laravel-api php artisan queue:work redis --queue=bin-lookups --once

   # Check Redis queue
   docker-compose exec redis redis-cli -a redis_password LLEN "bin-lookup-system-database-queues:bin-lookups"
   ```

### Code Structure

**Laravel Backend** (`be/`)

```
app/
├── Http/Controllers/      # API controllers
├── Http/Resources/        # API response formatting
├── Models/               # Eloquent models with UUID traits
├── Repositories/         # Data access layer
├── Services/             # Business logic
├── Jobs/                 # Queue job classes
├── Enums/               # Status enumerations
└── Exceptions/          # Custom exception handling

database/
├── migrations/          # Database schema with UUIDs
└── seeders/            # Sample data

routes/
├── api.php             # API routes
└── web.php             # Web routes (Inertia fallback)
```

**Vue.js Frontend** (`fe/`)

```
src/
├── components/         # Reusable Vue components
├── views/             # Page components
├── stores/            # Pinia state management
├── router/            # Vue Router configuration
├── assets/            # Static assets
└── types/             # TypeScript interfaces
```

### Adding New Features

1. **Backend Feature**:

   ```bash
   # Create migration
   php artisan make:migration create_new_table

   # Create model with UUID
   php artisan make:model NewModel
   # Add HasUuids trait to model

   # Create controller
   php artisan make:controller Api/NewController --api

   # Create resource
   php artisan make:resource NewResource
   ```

2. **Frontend Feature**:

   ```bash
   # Create new view
   touch fe/src/views/NewFeature.vue

   # Create store
   touch fe/src/stores/newFeature.ts

   # Add route
   # Edit fe/src/router/index.ts
   ```

## Troubleshooting

### Common Issues

**Services Won't Start**

```bash
# Check port conflicts
lsof -i :8000 -i :5173 -i :3306 -i :6379 -i :8080

# Clean and rebuild
docker-compose down -v
docker-compose up --build -d

# Check logs
docker-compose logs -f
```

**Database Connection Issues**

```bash
# Check MySQL container
docker-compose ps mysql

# Verify credentials
docker-compose exec mysql mysql -u bin_user -p bin_lookup_system

# Reset database
docker-compose exec laravel-api php artisan migrate:fresh --seed
```

**Queue Jobs Not Processing**

```bash
# Check queue worker status
docker-compose ps queue-worker

# View worker logs
docker-compose logs queue-worker

# Manual processing
docker-compose exec laravel-api php artisan queue:work redis --queue=bin-lookups --stop-when-empty
```

**Frontend Build Issues**

```bash
# Rebuild frontend container
docker-compose build vue-frontend

# Check Node.js logs
docker-compose logs vue-frontend

# Clear npm cache
docker-compose exec vue-frontend npm cache clean --force
```

### Performance Optimization

**Database Optimization**

- UUID indexing on frequently queried columns
- Composite indexes for filter combinations
- Regular ANALYZE TABLE for statistics

**Queue Optimization**

- Dedicated queue worker containers
- Queue priority management
- Failed job cleanup scheduling

**Redis Optimization**

- Memory usage monitoring
- Key expiration policies
- Connection pooling

## Production Deployment

### Pre-Production Checklist

1. **Security Configuration**:

   ```bash
   # Update environment
   APP_ENV=production
   APP_DEBUG=false

   # Strong passwords
   DB_PASSWORD=<generated-strong-password>
   REDIS_PASSWORD=<generated-strong-password>

   # SSL configuration
   # Update nginx configs for HTTPS
   ```

2. **Performance Settings**:

   ```bash
   # PHP-FPM optimization
   # Update docker/php/php.ini

   # Redis memory limits
   # Configure Redis maxmemory policies

   # MySQL optimization
   # Tune my.cnf for production workload
   ```

3. **Monitoring Setup**:
   - Application logging aggregation
   - Database performance monitoring
   - Queue length alerting
   - Container health checks

### Scaling Considerations

**Horizontal Scaling**:

- Multiple Laravel API containers behind load balancer
- Dedicated queue worker nodes
- Redis cluster for high availability
- MySQL read replicas for read-heavy workloads

**Vertical Scaling**:

- Increased container resource limits
- Optimized PHP-FPM pool configurations
- Enhanced MySQL buffer pool size
- Redis memory allocation tuning

### Backup Strategy

```bash
# Database backup
docker-compose exec mysql mysqldump -u root -p bin_lookup_system > backup.sql

# Redis backup
docker-compose exec redis redis-cli -a redis_password BGSAVE

# Application files
tar -czf app-backup.tar.gz bin-lookup-system/ fe/
```
