.PHONY: set-version prepare-host cleanup-host install-23 install-24 old-server-start server7-start server8-start dev-server8-start stop-server tests run-tests jenkins-test exec cypress uninstall uninstall-mapp empty-carts flush upgrade log-debug plugin-backup plugin-restore plugin-copy-app-to-volume plugin-install check

-include .env
export

PHP8=webdevops/php-apache:8.5
USER_NAME := $(shell id -un)
USER_ID := $(shell id -u)
GROUP_ID := $(shell id -g)
USER_GROUP = $(USER_ID):$(GROUP_ID)

prepare-host:
	bash ./Test/E2E/install/prepare_host.sh

check:
	@bash ./Test/E2E/install/check.sh
	
cleanup-host:
	docker exec -t local.domain.com bash -c "rm -f -R /home/application/app/*"
	
install:
	docker exec -t local.domain.com bash -c "/runner.sh install"

start-server:
	make prepare-host
	make check
	cd ./Test/E2E/install && MAGENTO_VERSION=2.4-develop && export PHPIMAGE=$(PHP8) && docker compose up -d
	# the cache folder is not in the image, so Docker creates the volume owned by root
	docker exec -u root local.domain.com chown application:application /home/application/.cache/composer
	
dev-server-start:
	make check && cd ./Test/E2E/install && export PHPIMAGE=$(PHP8) && docker compose up -d

stop-server:
	cd ./Test/E2E/install && export PHPIMAGE=$(PHP8) && docker compose down
	
tests:
	make empty-carts
	docker exec -t cypress bash -c "/cypress_run.sh $(USER_NAME) $(USER_ID) $(GROUP_ID)"

exec:
	docker exec -it local.domain.com bash
	
cypress:
	docker exec -it cypress bash
	
uninstall:
ifeq ($(shell uname -s),Darwin)
	# deleting ~100k files through the Docker Desktop file sharing takes ages, natively it takes seconds
	find ./Test/E2E/install/app -mindepth 1 ! -regex '\./Test/E2E/install/app/source.*' -delete
	docker exec -t local.domain.com bash -c "/runner.sh reset_db"
else
	docker exec -t local.domain.com bash -c "/runner.sh uninstall"
endif
	
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

plugin-install:
	docker exec -t local.domain.com bash -c "/runner.sh install_plugin"

get-magento-version:
	@docker exec -t local.domain.com php -r "require '/home/application/app/vendor/composer/InstalledVersions.php';echo(Composer\InstalledVersions::getVersion('magento/magento2-base'));"

set-version:
	@if [ -z "$(version)" ]; then \
        echo "Error: version parameter is not set. Set like: make set-version version=1.2.3"; \
        exit 1; \
    fi
	sed -i 's/"version":\s*"[^"]*"/"version": "$(version)"/' ./composer.json
	sed -i 's/psVersion\s=\s"[0-9]\+.[0-9]\+.[0-9]\+";/psVersion = "$(version)";/' ./Helper/TrackingScript.php

update-smartpixel:
	curl https://raw.githubusercontent.com/mapp-digital/Webtrekk-Smart-Pixel/master/packages/core/dist/smart-pixel.min.js --output ./view/frontend/web/js/smartpixel.min.js

zip:
	bash ./zip.sh	
