.PHONY: set-version prepare-host cleanup-host install-23 install-24 old-server-start server7-start server8-start dev-server8-start stop-server tests run-tests jenkins-test exec cypress uninstall uninstall-mapp empty-carts flush upgrade log-debug plugin-backup plugin-restore plugin-copy-app-to-volume plugin-install check

-include .env
export

PHP8=webdevops/php-apache:8.1
USER_NAME := $(shell id -un)
USER_ID := $(shell id -u)
GROUP_ID := $(shell id -g)
USER_GROUP = $(USER_ID):$(GROUP_ID)

prepare-host:
	bash ./E2E/install/prepare_host.sh

check:
	@bash ./E2E/install/check.sh
	
cleanup-host:
	docker exec -t local.domain.com bash -c "rm -f -R /home/application/app/*"
	
install:
	docker exec -t local.domain.com bash -c "/runner.sh install"

start-server:
	make prepare-host
	make check
	cd ./E2E/install && MAGENTO_VERSION=2.4-develop && export PHPIMAGE=$(PHP8) && docker-compose up -d
	
dev-server-start:
	make check && cd ./E2E/install && export PHPIMAGE="webdevops/php-apache-dev:8.1" && docker-compose up -d

stop-server:
	cd ./E2E/install && export PHPIMAGE=$(PHP8) && docker-compose down
	
tests:
	make empty-carts
	docker exec -t cypress bash -c "/cypress_run.sh $(USER_NAME) $(USER_ID) $(GROUP_ID)"

exec:
	docker exec -it local.domain.com bash
	
cypress:
	docker exec -it cypress bash
	
uninstall:
	docker exec -t local.domain.com bash -c "/runner.sh uninstall"
	
uninstall-mapp:
	docker exec -t local.domain.com bash -c "/runner.sh uninstall_mapp"

empty-carts:
	docker exec -t local.domain.com bash -c "/runner.sh empty_carts"
	
flush: 
	docker exec -t local.domain.com bash -c "/runner.sh flush"

reindex:
	docker exec -t local.domain.com bash -c "/runner.sh reindex"
	
upgrade: 
	docker exec -t local.domain.com bash -c "/runner.sh upgrade"

log-debug:
	docker exec -t local.domain.com bash -c "/runner.sh print_debug_log"

plugin-backup:
	docker exec -t local.domain.com bash -c "/runner.sh copy_plugin_app_to_backup"
	
plugin-restore:
	docker exec -t local.domain.com bash -c "/runner.sh copy_plugin_backup_to_app"

plugin-copy-app-to-volume:
	docker exec -t local.domain.com bash -c "/runner.sh copy_plugin_app_to_volume"

plugin-install:
	docker exec -t local.domain.com bash -c "/runner.sh copy_plugin_volume_to_app"

get-magento-version:
	@docker exec -t local.domain.com php -r "require '/home/application/app/vendor/composer/InstalledVersions.php';echo(Composer\InstalledVersions::getVersion('magento/magento2-base'));"


set-version:
	@if [ -z "$(version)" ]; then \
        echo "Error: version parameter is not set. Set like: make set-version version=1.2.3"; \
        exit 1; \
    fi
	sed -i 's/"version":\s*"[^"]*"/"version": "$(version)"/' ./src/MappDigital/Cloud/composer.json
	sed -i 's/psVersion\s=\s"[0-9]\+.[0-9]\+.[0-9]\+";/psVersion = "$(version)";/' ./src/MappDigital/Cloud/Helper/TrackingScript.php

zip:
	rm -f *.zip && cd src && zip -r ../Mapp_Cloud_Magento_$$(jq -r '.version' ./MappDigital/Cloud/composer.json).zip ./MappDigital
	cd ./src/MappDigital/Cloud && zip -r ../../../Mapp_Cloud_Magento_For_Marketplace_$$(jq -r '.version' ./composer.json).zip ./*
	
