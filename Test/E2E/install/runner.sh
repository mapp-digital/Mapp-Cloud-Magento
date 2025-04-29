#!/bin/bash

function log {
	tput setaf 5; echo $1
	tput sgr0
}

function uninstall {
	log "Reset app directory..."
	log "Delete existing data in app directory..."
        find /home/application/app/ -mindepth 1 ! -regex '^/home/application/app/source.*' -delete
        
        wait_for_db
        log "Resetting database..."
	php /db.php drop_db
}

function empty_carts {
	log "Trying to empty all carts via DB..."
	php /db.php drop_carts
}

function install {
	if [ -f /home/application/app/bin/magento ]
	then
		log "Magento already installed - uninstall first with make uninstall"
	else
		log "Get Magento repo via composer"
		cd /home/application/ && composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition app

		install_plugin
		
		wait_for_db

		log "Install Magento 2... "
		
		php /home/application/app/bin/magento setup:install \
		--admin-firstname=John \
		--admin-lastname=Doe \
		--admin-email=sascha.stieglitz@mapp.com \
		--admin-user=admin \
		--admin-password='test1234' \
		--base-url=http://local.domain.com \
		--base-url-secure=https://local.domain.com \
		--backend-frontname=admin \
		--db-host=mysql \
		--db-name=magento \
		--db-user=root \
		--db-password=root \
		--use-rewrites=1 \
		--language=en_US \
		--currency=EUR \
		--timezone=America/New_York \
		--use-secure-admin=1 \
		--admin-use-security-key=1 \
		--session-save=files \
		--search-engine=opensearch \
		--opensearch-host=os01 \
		--opensearch-port=9200


		log "Disable Two Factor Authorization"
		/home/application/app/bin/magento module:disable Magento_AdminAdobeImsTwoFactorAuth
		/home/application/app/bin/magento module:disable Magento_TwoFactorAuth

		log "Configure Mapp Cloud plugin..."
		/home/application/app/bin/magento config:set tagintegration/general/enable 1
		/home/application/app/bin/magento config:set tagintegration/general/tagintegration_id 136699033798929
		/home/application/app/bin/magento config:set tagintegration/general/tagintegration_domain responder.wt-safetag.com
		/home/application/app/bin/magento config:set tagintegration/general/attribute_blacklist customerPasswordHash,customerRpToken,customerRpTokenCreatedAt
		/home/application/app/bin/magento config:set mapp_gtm/general/gtm_enable 1
		/home/application/app/bin/magento config:set mapp_gtm/general/gtm_load 1
		/home/application/app/bin/magento config:set mapp_gtm/general/gtm_id GTM-WBQK267
		/home/application/app/bin/magento config:set mapp_gtm/general/gtm_add_to_cart_eventname gtm-add-to-cart

		log "Setting theme to Blank..."
		php /db.php set_blank_theme

		log "Creating test products and customer account..."
		php /testdata.php

		log "Finish up installation..."
		/home/application/app/bin/magento indexer:reindex
		/home/application/app/bin/magento setup:upgrade
		/home/application/app/bin/magento maintenance:disable
		wait_for_magento
		
		log "Done, you can now reach the store in you browser."
		log "Make sure to add this to your /etc/hosts:"
		log "127.0.1.1	local.domain.com"
		log "-----------------------"
		log "https://local.domain.com"
		log "https://local.domain.com/admin"
		log "User: admin"
		log "Password: test1234"
		log "-----------------------"
	fi	
}

function install_plugin {
	if [ -d /home/application/app/app/code ]
	then
		log "Deleting existing plugin..."
		rm -rf /home/application/app/app/code
		mkdir -p /home/application/app/app/code/MappDigital/Cloud
	fi
	if [ ! -d /home/application/app/app/code/MappDigital/Cloud ]
	then
		mkdir -p /home/application/app/app/code/MappDigital/Cloud
	fi
	log "Copy plugin files into app..."
	cp /home/application/plugin/composer.json /home/application/app/app/code/MappDigital/Cloud
	cp /home/application/plugin/registration.php /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/Api /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/Controller /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/Enum /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/Framework /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/Observer /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/Block /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/Console /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/Cron /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/Helper /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/Logger /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/Model /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/Plugin /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/Setup /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/etc /home/application/app/app/code/MappDigital/Cloud
	cp -r /home/application/plugin/view /home/application/app/app/code/MappDigital/Cloud
	log "Done, plugin copied to Magento application"
}

function upgrade {
	/home/application/app/bin/magento setup:upgrade
}

function flush {
        /home/application/app/bin/magento cache:flush
}

function reindex {
	    /home/application/app/bin/magento indexer:reindex
}

function wait_for_db {
	log "Waiting for database..."
	bash -c "/wait-for-it.sh -t 0 mysql:3306"
	log "Database found"
}

function wait_for_magento {
	log "Waiting for Magento Store..."
	bash -c "/wait-for-it.sh -t 0 local.domain.com:443"
	log "Magento Store found"
}

function print_debug_log {
	if [ -f /home/application/app/var/log/system.log ]
	then
		cat /home/application/app/var/log/system.log
	else
		log "Nothing has been logged so far..."
	fi
}

for arg; do
  tput setaf 2; echo "Invoking function $arg..."
  tput sgr0
  $arg
done
