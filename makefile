#!/usr/bin/make

.SILENT: clean
.PHONY: all
.DEFAULT_GOAL := help

##@ Development resources

setup: ## Setup the project
	docker-compose down -v
	docker-compose pull
	docker-compose up -d --build --remove-orphans --force-recreate
	sleep 5
	make setup-database

setup-database: ## Setup the database
	make database-cleanup
	make database-migrate

database-cleanup: ## Cleanup the database
	docker-compose exec mongo bash -c "mongosh \"mongodb://mongo/default?replicaSet=rs0&readPreference=primary\" --quiet --eval 'db.getCollectionNames().forEach(c => db.getCollection(c).drop());'"

database-migrate: ## Migrate the database
	docker-compose exec mongo bash -c "find /migrations/ -name \"*.sh\" -exec {} \;"

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
