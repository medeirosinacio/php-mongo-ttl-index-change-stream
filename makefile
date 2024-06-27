#!/usr/bin/make

.DEFAULT_GOAL := help

##@ Development resources

setup: ## Setup the project
	@chmod +x ./.docker/mongo/init.sh
	@chmod +x ./migrations/mongo/records_collection.sh
	docker-compose up -d --build --remove-orphans --force-recreate
