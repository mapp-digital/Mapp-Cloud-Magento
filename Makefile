.PHONY: prepare-host cleanup-host install-23 install-24 old-server-start server7-start server8-start dev-server8-start stop-server tests run-tests jenkins-test exec cypress uninstall uninstall-mapp empty-carts flush upgrade log-debug plugin-backup plugin-restore plugin-copy-app-to-volume plugin-install check

PHP7=webdevops/php-apache:7.4
PHP8=webdevops/php-apache:8.1
USER_NAME := $(shell id -un)
USER_ID := $(shell id -u)
GROUP_ID := $(shell id -g)
USER_GROUP = $(USER_ID):$(GROUP_ID)

prepare-host:
	bash ./E2E/install/prepare_host.sh

check:
	bash ./E2E/install/check.sh
	
cleanup-host:
	docker exec -t local.domain.com bash -c "rm -f -R /app/*"
	
install-23:
	make prepare-host
	export MAGENTO_VERSION=tags/2.3.4  && make old-server-start
	docker exec -t local.domain.com bash -c "/runner.sh set_version install"
	
install-24:
	make prepare-host
	export MAGENTO_VERSION=2.4-develop && make server8-start
	docker exec -t local.domain.com bash -c "/runner.sh set_version install"

old-server-start:
	make check && cd ./E2E/install && export PHPIMAGE=webdevops/php-apache:7.2 && docker-compose up -d

server7-start:
	make check && cd ./E2E/install && MAGENTO_VERSION=2.4-develop && export PHPIMAGE=$(PHP7) && docker-compose up -d

server8-start:
	make check && cd ./E2E/install && MAGENTO_VERSION=2.4-develop && export PHPIMAGE=$(PHP8) && docker-compose up -d
	
dev-server8-start:
	make check && cd ./E2E/install && export PHPIMAGE="webdevops/php-apache-dev:8.1" && docker-compose up -d

stop-server:
	cd ./E2E/install && docker-compose down
	
tests:
	make empty-carts
	docker exec -t cypress bash -c "/cypress_run.sh $(USER_NAME) $(USER_ID) $(GROUP_ID)"

run-tests:
	make server8-start
	make empty-carts
	docker exec -t cypress bash -c "/cypress_run.sh $(USER_NAME) $(USER_ID) $(GROUP_ID)"
	make stop-server
		
jenkins-test:
	make prepare-host
	chmod 777 ./E2E/install/app
	make server8-start
	make uninstall
	make uninstall-mapp
	make install-24
	make tests
	make cleanup-host
	make stop-server

jenkins-test-complete:
	make prepare-host
	chmod 777 ./E2E/install/app
	make server8-start
	make uninstall
	make install-23
	make tests
	make uninstall
	make install-24
	make tests
	make cleanup-host
	make stop-server

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
