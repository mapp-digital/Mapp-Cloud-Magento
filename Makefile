.PHONY: prepare-host install-24 server-start dev-server-start stop-server tests run-tests jenkins-test exec cypress uninstall uninstall-mapp flush upgrade

PHP=webdevops/php-apache:7.4

prepare-host:
	bash ./E2E/install/prepare_host.sh
	
install-24:
	make prepare-host
	export MAGENTO_VERSION=2.4-develop  && make server-start
	docker exec -t local.domain.com bash -c "/runner.sh set_version install"

server-start:
	cd ./E2E/install && export PHPIMAGE=$(PHP) && docker-compose up -d
	
dev-server-start:
	cd ./E2E/install && export PHPIMAGE="webdevops/php-apache-dev:7.4" && docker-compose up -d

stop-server:
	export PHPIMAGE=$(PHP) && cd ./E2E/install && docker-compose down
	
tests:
	docker exec -t cypress bash -c "cypress run"

run-tests:
	make server-start
	docker exec -t cypress bash -c "cypress run"
	make stop-server
		
jenkins-test:
	make prepare-host
	make uninstall
	make uninstall-mapp
	make install-24
	make tests
	make stop-server

exec:
	docker exec -it local.domain.com bash
	
cypress:
	docker exec -it cypress bash
	
uninstall:
	make server-start && docker exec -t local.domain.com bash -c "/runner.sh uninstall"
	
uninstall-mapp:
	make server-start && docker exec -t local.domain.com bash -c "/runner.sh uninstall_mapp"
	
flush: 
	docker exec -t local.domain.com bash -c "/runner.sh flush"
	
upgrade: 
	docker exec -t local.domain.com bash -c "/runner.sh upgrade"
	


