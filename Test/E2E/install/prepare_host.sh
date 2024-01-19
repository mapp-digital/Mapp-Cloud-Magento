#!/bin/bash

function log {
	tput setaf 5; echo $1
	tput sgr0
}

log "Checking temporary app volume directory..."
if [ ! -d "./Test/E2E/install/app" ]
then
	log "Temporary app directory not found - creating it..."
	mkdir ./Test/E2E/install/app
else
	log "Temporary app directory for app volume found!"
fi

log "Setting file permissions for scripts..."
chmod +x ./Test/E2E/install/runner.sh 
chmod +x "./Test/E2E/install/wait-for-it.sh"
chmod +x ./Test/E2E/install/db.php 
chmod +x ./Test/E2E/cypress_entrypoint.sh 
chmod +x ./Test/E2E/install/check.sh 
