#!/bin/bash

function log {
	tput setaf 5; echo $1
	tput sgr0
}

log "Checking temporary app volume directory..."
if [ ! -d "./E2E/install/app" ]
then
	log "Temporary app directory not found - creating it..."
	mkdir ./E2E/install/app
else
	log "Temporary app directory for app volume found!"
fi

log "Setting file permissions for scripts..."
chmod +x ./E2E/install/runner.sh 
chmod +x "./E2E/install/wait-for-it.sh"
chmod +x ./E2E/install/db.php 
chmod +x ./E2E/cypress_entrypoint.sh 
chmod +x ./E2E/install/check.sh 
