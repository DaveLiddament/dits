OS_FAMILY :=
ifeq ($(OS),Windows_NT)
    OS_FAMILY = Windows
else
    UNAME_S := $(shell uname -s)
    ifeq ($(UNAME_S),Linux)
        OS_FAMILY = Linux
    endif
    ifeq ($(UNAME_S),Darwin)
        OS_FAMILY = Darwin
    endif
    UNAME_R := $(shell uname -r)
    ifneq ($(findstring WSL2,$(UNAME_R)),)
        OS_FAMILY = Linux
    endif
endif

export USER_ID :=
export DOCKER_USER :=
ifeq ($(OS_FAMILY),Linux)
    USER_ID = $(shell id -u)
    DOCKER_USER = --user "$(USER_ID):$(shell id -g)"
endif

DOCKER_COMP = docker compose
APP_EXEC = $(DOCKER_COMP) exec $(DOCKER_USER) app

.DEFAULT_GOAL = help

## —— Help ——————————————————————————————————————————————————————————————————————
.PHONY: help

help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Docker ———————————————————————————————————————————————————————————————————
.PHONY: build up start down logs

build: ## Builds the Docker images
	@$(DOCKER_COMP) build --pull --no-cache

up: ## Starts the container in detached mode
	@$(DOCKER_COMP) up --detach

start: build up ## Build and start the container

down: ## Stop the container
	@$(DOCKER_COMP) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

## —— App ——————————————————————————————————————————————————————————————————————
.PHONY: app/shell app/composer app/setup app/ci app/test app/tsm app/phpstan app/cs-fix app/cs app/lint app/composer-unused app/require-checker app/composer-validate

app/shell: ## Open a shell in the app container
	@$(APP_EXEC) bash

app/composer: ## Run composer. Pass the parameter "c=" to run a given command
	@$(eval c ?=)
	@$(APP_EXEC) composer $(c)

app/setup: ## Install all dependencies
	@$(APP_EXEC) composer setup

app/ci: ## Run local CI tasks
	@$(APP_EXEC) composer ci-local

app/test: ## Run tests. Pass the parameter "c=" to add options to phpunit
	@$(eval c ?=)
	@$(APP_EXEC) vendor/bin/phpunit $(c)

app/tsm: ## Run test-mapper
	@$(APP_EXEC) composer tsm

app/phpstan: ## Run PHPStan analysis
	@$(APP_EXEC) composer phpstan

app/cs-fix: ## Fix code style
	@$(APP_EXEC) composer cs-fix

app/cs: ## Check code style (dry run)
	@$(APP_EXEC) composer cs

app/lint: ## Run parallel-lint
	@$(APP_EXEC) composer lint

app/composer-unused: ## Check for unused composer packages
	@$(APP_EXEC) composer composer-unused

app/require-checker: ## Check for missing composer requirements
	@$(APP_EXEC) composer composer-require-checker

app/composer-validate: ## Validate composer.json
	@$(APP_EXEC) composer composer-validate

## —— Multi-PHP CI ————————————————————————————————————————————————————————————
.PHONY: app/ci-83 app/ci-84 app/ci-85 app/ci-all

app/ci-83: ## Run CI against PHP 8.3
	@$(DOCKER_COMP) run --rm app-php83 sh -c "composer setup && composer ci"

app/ci-84: ## Run CI against PHP 8.4
	@$(DOCKER_COMP) run --rm app-php84 sh -c "composer setup && composer ci"

app/ci-85: ## Run CI against PHP 8.5
	@$(DOCKER_COMP) run --rm app-php85 sh -c "composer setup && composer ci"

app/ci-all: app/ci-83 app/ci-84 app/ci-85 ## Run CI against all supported PHP versions
