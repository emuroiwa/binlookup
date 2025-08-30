.PHONY: help install test test-unit test-feature test-coverage clean setup dev

# Default target
help:
	@echo "BIN Lookup System - Available Commands:"
	@echo ""
	@echo "  install         Install PHP dependencies"
	@echo "  setup           Initial project setup (copy .env, generate key)"
	@echo "  dev             Start development server and queue worker"
	@echo "  test            Run all Pest tests"
	@echo "  test-unit       Run unit tests only"
	@echo "  test-feature    Run feature tests only" 
	@echo "  test-coverage   Run tests with coverage report"
	@echo "  clean           Clear Laravel caches"
	@echo ""

# Installation and setup
install:
	composer install

setup: install
	cp -n .env.example .env || true
	php artisan key:generate
	php artisan migrate

# Development
dev:
	@echo "Starting Laravel development server..."
	php artisan serve &
	@echo "Starting queue worker..."
	php artisan queue:work

# Testing with Docker (default)
test:
	@echo "Running Pest test suite..."
	docker-compose exec -T -e APP_ENV=testing -e DB_CONNECTION=mysql -e DB_DATABASE=bin_lookup_system_test laravel-api ./vendor/bin/pest

test-unit:
	@echo "Running unit tests..."
	docker-compose exec -T -e APP_ENV=testing -e DB_CONNECTION=mysql -e DB_DATABASE=bin_lookup_system_test laravel-api ./vendor/bin/pest tests/Unit/

test-feature:
	@echo "Running feature tests..."
	docker-compose exec -T -e APP_ENV=testing -e DB_CONNECTION=mysql -e DB_DATABASE=bin_lookup_system_test laravel-api ./vendor/bin/pest tests/Feature/

test-coverage:
	@echo "Running tests with coverage..."
	docker-compose exec -T -e APP_ENV=testing -e DB_CONNECTION=mysql -e DB_DATABASE=bin_lookup_system_test laravel-api ./vendor/bin/pest --coverage

test-compact:
	@echo "Running tests with compact output..."
	docker-compose exec -T -e APP_ENV=testing -e DB_CONNECTION=mysql -e DB_DATABASE=bin_lookup_system_test laravel-api ./vendor/bin/pest --compact

# Local testing (without Docker)
test-local:
	@echo "Running Pest test suite locally..."
	APP_ENV=testing DB_CONNECTION=mysql DB_DATABASE=bin_lookup_system_test ./vendor/bin/pest

test-local-unit:
	@echo "Running unit tests locally..."
	APP_ENV=testing DB_CONNECTION=mysql DB_DATABASE=bin_lookup_system_test ./vendor/bin/pest tests/Unit/

test-local-feature:
	@echo "Running feature tests locally..."
	APP_ENV=testing DB_CONNECTION=mysql DB_DATABASE=bin_lookup_system_test ./vendor/bin/pest tests/Feature/

# Maintenance
clean:
	@echo "Clearing Laravel caches..."
	php artisan cache:clear
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear

# Database management
migrate:
	php artisan migrate

migrate-fresh:
	php artisan migrate:fresh

seed:
	php artisan db:seed

reset: migrate-fresh seed

# Docker commands
docker-up:
	docker-compose up -d

docker-down:
	docker-compose down

docker-logs:
	docker-compose logs -f laravel-api

# Production commands
optimize:
	composer install --optimize-autoloader --no-dev
	php artisan config:cache
	php artisan route:cache
	php artisan view:cache