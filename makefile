#!/usr/bin/make

.SILENT: clean
.PHONY: all
.DEFAULT_GOAL := help

##@ Development resources

setup: ## Setup the project
	docker-compose down -v
	rm -rf ./runtime/cache/*
	docker-compose pull
	docker-compose up -d --build --remove-orphans --force-recreate
	sleep 5
	@make install-dependencies
	make setup-database

ci: ## Run the CI pipeline
	docker-compose exec php bash -c "composer ci"

test: ## Run the tests
	docker-compose exec php bash -c "composer test:unit"

install-dependencies: ## Install the project dependencies
	docker-compose exec php bash -c "composer install --ignore-platform-reqs"

mongo-stream-listener: ## Start the MongoDB stream listener
	docker-compose exec php bash -c "/app/bin/console.php mongo:stream-listener"

setup-database: ## Setup the database
	make database-cleanup
	make database-migrate

database-cleanup: ## Cleanup the database
	docker-compose exec mongo bash -c "/usr/scripts/reset.sh"

database-migrate: ## Migrate the database
	docker-compose exec mongo bash -c "find /migrations/ -name \"*.sh\" -exec {} \;"

database-seed: ## Create a new record in the database
	docker-compose exec mongo bash -c "/usr/scripts/create-new-record.sh"

playground: ## Start a PHP playground dockerized environment
	@make check-docker
	@docker-compose exec php bash

check-docker: ## Check if Docker is installed
	@docker --version > /dev/null 2>&1 || (echo "Docker is not installed. Please install Docker and try again." && exit 1)

help: ## Show this help message
	@echo "Usage: make [command]"
	@echo ""
	@echo "Commands available:"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST) | sort
