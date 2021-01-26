# End to End test suite - Mapp Cloud / Magento 2

The server can be reached under

https://local.domain.com

&nbsp;

## Makefile directives 
&nbsp;

### prepare-host 
    Adds execution rights to scripts, created app directory for app volume within E2E/install directory
 
### install-24
    Starts Mapp network and installs latest 2.4-developer version of Magento by pulling the repos, checking the version out and then starting the install process.
 
### server-start
    Starts the server -> needs to be installed first. Will be saved in volume. Creates network 'Mapp'.
 
### dev-server-start
    Same as server-start, but with xdebug activated (which is slower).

### stop-server
    Stops the server, shuts down docker network 'Mapp'.
 
### tests
    Runs the tests, given that the server is already started.
 
### run-tests
    Starts the server, runs the tests, closes the server.
 
### jenkins-test
    Complete test routine for Jenkins: host will be prepared, server started, app-volume reset, db reset, version 2.4-developer installed, tests will run, server will be stopped.
 
### exec 
    Bash shell into Magento container.

### cypress 
    Bash shell into Cypress container.
 
### uninstall
    Uninstalls currently installed Magento version. Will keep the Magento repos under /app/sources though.
 
### flush
    Flushed the Magento 2 cache.
 
### upgrade
    Runs setup:upgrade of the Magento binary.  

---
 &nbsp;
 ## Xdebug config
 Mapping in IntelliJ like this:
 ![IntelliJ settings](./assets/xdebug_IntelliJ_server.png)