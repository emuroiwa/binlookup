.PHONY: help build up down logs shell migrate test clean

# Default target
help:
	@echo "BIN Lookup System - Docker Commands"
	@echo ""
	@echo "Main Commands:"
	@echo "  make up        - Start all services"
	@echo "  make down      - Stop all services"
	@echo "  make build     - Build and start all services"
	@echo "  make logs      - Show logs from all services"
	@echo ""
	@echo "Development:"
	@echo "  make shell     - Access Laravel container shell"
	@echo "  make migrate   - Run Laravel migrations"
	@echo "  make fresh     - Fresh database with migrations"
	@echo "  make test      - Run Laravel tests"
	@echo ""
	@echo "Maintenance:"
	@echo "  make clean     - Clean up containers and volumes"
	@echo "  make restart   - Restart all services"
	@echo ""
	@echo "Access Points:"
	@echo "  Frontend:      http://localhost:5173"
	@echo "  API:           http://localhost:8000"
	@echo "  phpMyAdmin:    http://localhost:8080"

# Build and start all services
build:
	cp .env.docker bin-lookup-system/.env
	docker-compose up --build -d
	@echo "Waiting for services to start..."
	sleep 10
	make migrate
	@echo ""
	@echo "🚀 Services are ready!"
	@echo "Frontend: http://localhost:5173"
	@echo "API: http://localhost:8000"
	@echo "phpMyAdmin: http://localhost:8080"

# Start services
up:
	cp .env.docker bin-lookup-system/.env
	docker-compose up -d
	@echo ""
	@echo "🚀 Services started!"
	@echo "Frontend: http://localhost:5173"
	@echo "API: http://localhost:8000"
	@echo "phpMyAdmin: http://localhost:8080"

# Stop services
down:
	docker-compose down

# Show logs
logs:
	docker-compose logs -f

# Access Laravel shell
shell:
	docker-compose exec laravel-api sh

# Run migrations
migrate:
	docker-compose exec laravel-api php artisan migrate --force

# Fresh database
fresh:
	docker-compose exec laravel-api php artisan migrate:fresh --force

# Run tests
test:
	docker-compose exec -T -e APP_ENV=testing -e DB_CONNECTION=mysql -e DB_DATABASE=bin_lookup_system_test laravel-api php artisan test

# Clean up
clean:
	docker-compose down -v --remove-orphans
	docker system prune -f

# Restart services
restart:
	make down
	make up