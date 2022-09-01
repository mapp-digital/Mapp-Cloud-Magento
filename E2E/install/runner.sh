#!/bin/bash

function log {
	tput setaf 5; echo $1
	tput sgr0
}

function copy_plugin_app_to_backup {
	if [ -d /app/app/code/MappDigital ]
	then
		log "Backup plugin to source folder..."
		cp -r -f -T /app/app/code/MappDigital /app/source/MappDigital
	fi
}

function copy_plugin_backup_to_app {
	if [ -d /app/source/MappDigital ]
	then
		log "Restore plugin from backup into app..."
		cp -r -f -T  /app/source/MappDigital /app/app/code/MappDigital
	else
		log "No backup found!"
	fi
}

function copy_plugin_app_to_volume {
	if [ -d /app/app/code/MappDigital ]
	then
	log "Copy plugin version from app to volume directory (src directory)..."
	cp -r -f -T /app/app/code/MappDigital /plugincode/MappDigital
	else
		log "Plugin not yet installed - install with"
		log "make plugin-install"
	fi
}

function copy_plugin_volume_to_app {
	log "Copy plugin version from volume (src directory) into app directory..."
	cp -r -f -T /plugincode/MappDigital /app/app/code/MappDigital
}

function set_version {
	log "Making sure source directory exists..."
	if [ ! -d "/app/source" ]
	then
	    mkdir /app/source
	    log "Source directory created!"
	fi

	log "Checking for existing Mag2 repo..."
	if [ ! -d "/app/source/app" ]
	then
	    log "No Mag2 repo found - start download..." && cd /app/source/
	    git clone https://github.com/magento/magento2.git app
	else
	    log "Mag2 repo found!"
	fi
	
	# log "Checking for existing sample data repo..."
	# if [ ! -d "/app/source/data" ]
	# then
	#     log "No repo found - start download..." && cd /app/source/
	#     git clone https://github.com/magento/magento2-sample-data.git data
	# else
	#     log "Repo with sample data found!"
	# fi
	
	log "Updating repos..."
	cd /app/source/app && git pull origin
	# cd /app/source/data && git pull origin
	
	log "Checking out version $MAGENTO_VERSION..."
	cd /app/source/app &&  git checkout $MAGENTO_VERSION
	# cd /app/source/data &&  git checkout $MAGENTO_VERSION
}

function uninstall {
	log "Reset app directory..."
	
	log "Backing up plugin code..."
	cp -r -f /app/app/code/MappDigital /app/source/MappDigital

	log "Delete existing data in app directory..."
        find /app/ -mindepth 1 ! -regex '^/app/source.*' -delete
        
        wait_for_db
        log "Resetting database..."
	php /db.php drop_db
}

function uninstall_mapp {
	log "Deleting backup of Mapp Cloud plugin..."
	rm -r -f /app/source/MappDigital
}

function empty_carts {
	log "Trying to empty all carts via DB..."
	php /db.php drop_carts
}

function install {
	if [ -f /app/bin/magento ]
	then
		log "Magento already installed - uninstall first with make uninstall"
	else
		log "Copy data from source to app directory..."
		cp -r /app/source/app/* /app/
		# mkdir /app/magento2-sample-data/
		# cp -r /app/source/data/* /app/magento2-sample-data/
		
		log "Getting plugin code..."
		if [ -d /app/source/MappDigital ]
		then
			log "Found code backup - using the backup code..."
			copy_plugin_backup_to_app
		else
			log "No backup of plugin code found - using code from src volume..."
			copy_plugin_volume_to_app
		fi
		
		# log "Setting sample data permissions..."
		# php -f /app/magento2-sample-data/dev/tools/build-sample-data.php -- --ce-source="/app/"
		# chown -R :application /app/magento2-sample-data/
		
		wait_for_db
		log "Install composer components..."
		cd /app
		composer install

		log "Install Magento 2... "
		if [[ $MAGENTO_VERSION = tags/2.3.4 ]]
		then
			log "Found 2.3.4 Version, leave out elasticsearch-host parameter..."
			php /app/bin/magento setup:install \
			--admin-firstname=John \
			--admin-lastname=Doe \
			--admin-email=sascha.stieglitz@mapp.com \
			--admin-user=admin \
			--admin-password='test1234' \
			--base-url=https://local.domain.com \
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
			--session-save=files

		else
			php /app/bin/magento setup:install \
			--admin-firstname=John \
			--admin-lastname=Doe \
			--admin-email=sascha.stieglitz@mapp.com \
			--admin-user=admin \
			--admin-password='test1234' \
			--base-url=https://local.domain.com \
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
			--elasticsearch-host=es01

		fi
		/app/bin/magento setup:upgrade

		log "Configure Mapp Cloud plugin..."
		/app/bin/magento config:set tagintegration/general/enable 1
		/app/bin/magento config:set tagintegration/general/tagintegration_id 136699033798929
		/app/bin/magento config:set tagintegration/general/tagintegration_domain responder.wt-safetag.com
		/app/bin/magento config:set tagintegration/general/attribute_blacklist customerPasswordHash,customerRpToken,customerRpTokenCreatedAt
		/app/bin/magento config:set mapp_gtm/general/gtm_enable 1
		/app/bin/magento config:set mapp_gtm/general/gtm_load 1
		/app/bin/magento config:set mapp_gtm/general/gtm_id GTM-WBQK267
		/app/bin/magento config:set mapp_gtm/general/gtm_add_to_cart_eventname gtm-add-to-cart

		log "Setting theme to Blank..."
		php /db.php set_blank_theme

		log "Creating test products and customer account..."
		php /testdata.php

		log "Finish up installation..."
		/app/bin/magento indexer:reindex
		/app/bin/magento setup:upgrade
		/app/bin/magento cache:flush
		/app/bin/magento maintenance:disable
		
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

function upgrade {
	/app/bin/magento setup:upgrade
}

function flush {
        /app/bin/magento cache:flush
}

function wait_for_db {
	log "Waiting for database..."
	bash -c "/wait-for-it.sh -t 0 mysql:3306"
	log "Database found"
}

function print_debug_log {
	if [ -f /app/var/log/system.log ]
	then
		cat /app/var/log/system.log
	else
		log "Nothing has been logged so far..."
	fi
}

for arg; do
  tput setaf 2; echo "Invoking function $arg..."
  tput sgr0
  $arg
done

