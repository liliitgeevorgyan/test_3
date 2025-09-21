.PHONY: help build up down restart logs shell test migrate fresh install

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build the Docker containers
	docker-compose build

up: ## Start the services
	docker-compose up -d

down: ## Stop the services
	docker-compose down

restart: ## Restart the services
	docker-compose restart

logs: ## Show logs
	docker-compose logs -f

shell: ## Open shell in app container
	docker-compose exec app bash

test: ## Run tests
	docker-compose exec app php artisan test

migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

fresh: ## Fresh migration with seeding
	docker-compose exec app php artisan migrate:fresh

install: ## Install dependencies and setup
	docker-compose exec app composer install
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan migrate

queue: ## Start queue worker
	docker-compose exec app php artisan queue:work

setup: build up install ## Complete setup (build, start, install)
	@echo "Setup complete! Service available at http://localhost:8080"
