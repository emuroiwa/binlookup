# BIN Lookup System (API)

A production-ready Laravel API for importing and processing Bank Identification Numbers (BINs) from CSV files, with automated external API lookups and comprehensive data management features.

## Features

-   **CSV Import API**: Upload CSV files containing BIN numbers via REST API
-   **Async Processing**: Queue-based background processing with database queues
-   **External API Integration**: Automated lookups using binlist.net or similar APIs
-   **Data Management API**: RESTful endpoints for filtering, searching, and sorting BIN data
-   **Excel Export API**: Export filtered results to XLSX format via API
-   **Real-time Progress**: Track import progress with detailed statistics via API
-   **Error Handling**: Robust error handling with retry logic and failure reporting

## Architecture

The system follows Domain-Driven Design (DDD) principles with a clean service layer architecture:

-   **Controllers**: Thin API controllers handling HTTP requests/responses
-   **Services**: Business logic layer (BinImportService, BinLookupService)
-   **Jobs**: Background queue processing (ProcessBinLookupJob)
-   **Models**: Eloquent models with proper relationships and casts
-   **Resources**: API response transformers for consistent JSON output

## Technology Stack

-   **Backend**: Laravel 11/12, PHP 8.2+
-   **Database**: MySQL/SQLite with proper indexing
-   **Queue**: Database queue system
-   **Cache**: File cache for API response caching
-   **Export**: Laravel Excel (Maatwebsite/Excel)
-   **HTTP Client**: Guzzle with retry logic and rate limiting
-   **Testing**: Pest PHP with comprehensive coverage

## Quick Start

### Prerequisites

-   PHP 8.2 or higher
-   Composer
-   SQLite (default) or MySQL
-   Web server (Apache/Nginx) or use built-in PHP server

### Installation

1. **Clone the repository**:

    ```bash
    git clone <repository-url>
    cd bin-lookup-system
    ```

2. **Install PHP dependencies**:

    ```bash
    composer install
    ```

3. **Environment setup**:

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. **Database setup**:

    ```bash
    # Run database migrations
    php artisan migrate

    # If you get migration conflicts with existing tables:
    php artisan migrate:status                    # Check migration status
    php artisan migrate:rollback --step=3         # Rollback conflicting migrations
    php artisan migrate                           # Re-run migrations
    ```

5. **Start the application**:

    ```bash
    # Start Laravel development server
    php artisan serve

    # Start queue workers (in separate terminal)
    php artisan queue:work
    ```

6. **Run tests** (optional):

    ```bash
    # Run the full test suite
    make test

    # Or run tests locally (without Docker)
    ./vendor/bin/pest
    ```

### Usage

#### REST API

The application provides a comprehensive REST API for programmatic access:

1. **Upload CSV File**: Send POST request to `/api/bin-imports` with CSV file
2. **Monitor Progress**: Check import status via `/api/bin-imports/{id}`
3. **View Results**: Browse processed BIN data at `/api/bin-data`
4. **Export Data**: Generate Excel exports via `/api/bin-data/export`

### CSV Format

Your CSV file should contain a `bin` column with 6-8 digit BIN numbers:

```csv
bin,bank,card,type,level,country,countrycode,website,phone
549781,,,,,,,,
530156,,,,,,,,
519203,,,,,,,,
```

See `storage/app/sample_bins.csv` for a complete example.

## API Endpoints

### BIN Imports

-   `GET /api/bin-imports` - List imports with pagination and filtering
-   `POST /api/bin-imports` - Create new import from CSV upload
-   `GET /api/bin-imports/{id}` - Get import details with statistics
-   `DELETE /api/bin-imports/{id}` - Delete import and related data

### BIN Data

-   `GET /api/bin-data` - List BIN data with filtering and pagination
-   `GET /api/bin-data/export` - Export filtered data to Excel
-   `GET /api/bin-data/filter-options` - Get available filter values

### Filtering Parameters

Both endpoints support comprehensive filtering:

-   `bin` - Filter by BIN number (prefix match)
-   `bank` - Filter by bank name (partial match)
-   `brand` - Filter by card brand (exact match)
-   `type` - Filter by card type (exact match)
-   `country` - Filter by country code or name
-   `date_from` / `date_to` - Date range filtering
-   `search` - General search across multiple fields
-   `sort` / `direction` - Column sorting
-   `per_page` - Results per page (default: 15-20)

## Testing

The application uses **Pest PHP** for modern, expressive testing with comprehensive coverage.

### Run Test Suite

```bash
# Run all tests using Make
make test

# Run all tests directly with Pest
./vendor/bin/pest

# Run with compact output
./vendor/bin/pest --compact

# Run specific test files
./vendor/bin/pest tests/Unit/BinImportServiceTest.php
./vendor/bin/pest tests/Feature/BinImportCsvUploadTest.php

# Run with coverage (requires Xdebug)
./vendor/bin/pest --coverage

# Run only Unit tests
./vendor/bin/pest tests/Unit/

# Run only Feature tests  
./vendor/bin/pest tests/Feature/
```

### Makefile Commands

The project includes a Makefile for convenient task management:

```bash
# View all available commands
make help

# Project setup
make install        # Install dependencies
make setup         # Copy .env, generate key, run migrations

# Testing
make test          # Run all tests (via Docker)
make test-unit     # Run unit tests only
make test-feature  # Run feature tests only
make test-coverage # Run with coverage report
make test-compact  # Run with compact output

# Local testing (without Docker)
make test-local    # Run all tests locally

# Development
make dev          # Start server and queue worker
make clean        # Clear Laravel caches

# Docker
make docker-up    # Start Docker containers
make docker-down  # Stop Docker containers
```

### Test Structure

The test suite includes **80 tests** across multiple categories:

#### **Unit Tests** (Service Layer)
-   **BinImportService**: CSV processing and import orchestration
-   **BinImportProcessingService**: File parsing and validation
-   **BinImportValidationService**: File format and structure validation
-   **BinLookupService**: External API integration and error handling
-   **BinApiService**: HTTP client with retry logic and rate limiting
-   **ProcessBinLookupJob**: Queue job processing and retry mechanisms
-   **Model Tests**: Relationships, accessors, and data integrity

#### **Feature Tests** (End-to-End API)
-   **CSV Upload**: File validation, processing, and import creation
-   **Import Management**: CRUD operations on imports with filtering
-   **BIN Data API**: Data retrieval with comprehensive filtering options
-   **XLSX Export**: Excel export functionality with applied filters

### Test Database

Tests use a dedicated MySQL test database (`bin_lookup_system_test`) with:
-   **RefreshDatabase**: Clean state for each test
-   **Factory Data**: Realistic test data generation
-   **Mocked Services**: External API calls and dependencies

### Key Test Coverage

-   **CSV Processing**: Validation, parsing, duplicate handling, invalid BIN filtering
-   **External API Integration**: Success, failures, rate limiting, retry logic
-   **Queue System**: Job dispatch, processing, failure handling, exponential backoff  
-   **Database Operations**: Relationships, constraints, transactions, data integrity
-   **API Endpoints**: Authentication, validation, filtering, pagination, error responses
-   **File Operations**: Upload validation, Excel export with various filter combinations

## Configuration

### Environment Variables

Key environment variables for production deployment:

```bash
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=bin_lookup_system
DB_USERNAME=bin_user
DB_PASSWORD=secure_password
QUEUE_CONNECTION=database
BIN_API_BASE_URL=https://lookup.binlist.net
```

### Queue Configuration

The system uses database queues by default. For production, consider:

```bash
# Process queues
php artisan queue:work

# Process specific queue
php artisan queue:work --queue=bin-lookups

# Run as daemon (production)
php artisan queue:work --daemon --sleep=3 --tries=3
```

## Architecture Decisions

### API Integration Strategy

**Decision**: Use Guzzle HTTP client with caching, rate limiting, and comprehensive error handling.

**Rationale**:

-   Caches responses for 1 hour to reduce API calls and improve performance
-   Implements exponential backoff for rate limiting (429 errors)
-   Distinguishes between retryable (connection, server errors) and non-retryable (client errors) failures
-   Configurable base URL and rate limits for different API providers

### Queue Architecture & Retry Logic

**Decision**: Database-based queue with job-level retry logic and exponential backoff.

**Configuration**:

-   Max 3 attempts per BIN lookup
-   Backoff delays: 30s, 2min, 5min
-   Job timeout: 2 minutes
-   Retry window: 2 hours

**Benefits**:

-   Handles temporary API outages gracefully
-   Prevents overwhelming external APIs
-   Provides detailed error tracking and reporting
-   No Redis dependency for simple deployments

### Error Handling Strategy

**Approach**: Multi-layered error handling with proper categorization.

**Layers**:

1. **Service Level**: Catch and categorize API errors (retryable vs permanent)
2. **Job Level**: Implement retry logic and failure handling
3. **Controller Level**: Transform exceptions into appropriate HTTP responses
4. **Database Level**: Use transactions for data consistency

### Performance Considerations

**For Thousands of BINs**:

1. **Batching**: Process CSV in 1000-row chunks for memory efficiency
2. **Chunked Queuing**: Dispatch jobs in batches of 100 to prevent memory issues
3. **Database Indexing**: Strategic indexes on frequently queried columns
4. **Caching**: File cache for duplicate BIN lookups across imports
5. **Rate Limiting**: Configurable delays between API calls

**Query Optimization**:

-   Use `select()` to limit retrieved columns
-   Implement eager loading for relationships
-   Database indexes on filter columns (bin_number, country_code, card_brand, etc.)

### Data Integrity During Async Processing

**Measures**:

1. **Database Transactions**: Wrap critical operations in DB transactions
2. **Unique Constraints**: Prevent duplicate BIN/import combinations
3. **Status Tracking**: Comprehensive status enums for imports and lookups
4. **Progress Counters**: Real-time progress tracking with atomic updates
5. **Idempotency**: Jobs can be safely retried without side effects

### Scalability for Multiple Concurrent Imports

**Horizontal Scaling**:

-   Queue workers can be scaled independently
-   Database supports concurrent reads/writes with proper locking
-   File cache handles concurrent caching efficiently

**Resource Management**:

-   Separate queue for BIN lookups (`bin-lookups`) to isolate load
-   Configurable job concurrency and timeout settings
-   Memory-efficient processing with chunked operations

**Monitoring & Observability**:

-   Structured logging with context (import ID, BIN numbers, error details)
-   Job failure tracking and alerting
-   Performance metrics for API response times and queue processing

## Production Deployment

### Web Server Configuration

#### Nginx Example

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/bin-lookup-system/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### Apache Example

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/bin-lookup-system/public

    <Directory /path/to/bin-lookup-system/public>
        Options Indexes MultiViews FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Process Management

For production, use a process manager like Supervisor to manage queue workers:

```ini
[program:bin-lookup-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/bin-lookup-system/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/supervisor/bin-lookup-queue.log
```

### Database Optimization

For production MySQL deployments:

```sql
-- Add indexes for better query performance
CREATE INDEX idx_bin_data_bin_number ON bin_data(bin_number);
CREATE INDEX idx_bin_data_country_code ON bin_data(country_code);
CREATE INDEX idx_bin_data_card_brand ON bin_data(card_brand);
CREATE INDEX idx_bin_data_created_at ON bin_data(created_at);
CREATE INDEX idx_bin_imports_status ON bin_imports(status);
```

## Security Considerations

1. **Input Validation**: All CSV uploads are validated and sanitized
2. **Rate Limiting**: API endpoints include rate limiting protection
3. **File Upload Security**: Restricted file types and size limits
4. **SQL Injection Protection**: Using Eloquent ORM with parameter binding
5. **CORS Configuration**: Configure allowed origins for API access

## Monitoring & Logging

The application includes comprehensive logging:

-   Import progress and errors
-   API integration failures and retries
-   Queue processing statistics
-   Performance metrics

Log files are stored in `storage/logs/laravel.log` and can be integrated with log aggregation systems.
