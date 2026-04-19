.PHONY: up down bash install-symfony

export BUILDER_GID := $(shell id -u)
export BUILDER_UID := $(shell id -g)

export APP_ENV=dev

UNAME_S := $(shell uname -s)
ifeq ($(UNAME_S),Linux)
	export REMOTE_HOST := $(shell ip -4 addr show docker0 | grep -Po 'inet \K[\d.]+')
endif
ifeq ($(UNAME_S),Darwin)
	export REMOTE_HOST := $(shell ifconfig | sed -En 's/127.0.0.1//;s/.*inet (addr:)?(([0-9]*\.){3}[0-9]*).*/\2/p' | sed -n '1p')
endif

export COMPOSER_HOME
ifndef COMPOSER_HOME
	ifneq ($(shell which composer),)
		COMPOSER_HOME := $(shell composer config --global home 2>/dev/null)
	endif
	ifeq ($(COMPOSER_HOME),)
		COMPOSER_HOME := $(HOME)/.composer
	endif
endif

export COMPOSER_CACHE_DIR
ifndef COMPOSER_CACHE_DIR
	ifneq ($(shell which composer),)
		COMPOSER_CACHE_DIR := $(shell composer config --global cache-dir 2>/dev/null)
	endif
	ifeq ($(COMPOSER_CACHE_DIR),)
		COMPOSER_CACHE_DIR := $(HOME)/.composer/cache
	endif
endif

ifeq ($(images_are_defined),)
	ifeq ($(APPLICATION_NAME),)
		export APPLICATION_NAME := $(shell cat .env | grep '^APPLICATION_NAME:' | sed 's/^APPLICATION_NAME://' | sed -e 's/^[[:space:]]*//')
	endif
endif

ifeq ($(APP_IMAGE),)
	export APP_IMAGE := php-fpm-${APPLICATION_NAME}-local
endif

ifeq ($(APP_CONTAINER),)
	export APP_CONTAINER := ${APPLICATION_NAME}_php
endif


install-symfony: build-app
	@echo "Symfony CLI is installed inside the PHP container."
	@docker run --rm ${APP_IMAGE} symfony version

build-app:: up composer-install run-migrations symfony-install web-server-run

up:
	docker compose -f docker-compose.yaml up -d

composer-install:
	docker compose exec -u root -i php composer install --no-scripts

run-migrations:
	docker compose exec -u root -i php php bin/console doctrine:migrations:migrate

symfony-install:
	docker compose exec -u root -i php sh /app/install_symfony.sh

web-server-run:
	docker compose exec -u root -i php symfony server:start --no-tls --listen-ip=0.0.0.0 --port=8000

down:
	docker compose down --remove-orphans

check-code-quality:
	docker compose exec -i php sh -c "./vendor/bin/php-cs-fixer fix --allow-risky=yes && ./vendor/bin/phpstan analyse -l 6 src tests"

run-tests:
	docker compose exec -i php sh -c "php bin/console --env=test doctrine:migrations:migrate && php bin/console --env=test doctrine:fixtures:load && php bin/phpunit"
