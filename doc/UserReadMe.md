# Developer Documentation

## Intro

This Mapp Cloud module is a combination of two previous packages. These were:
* `mappconnect/magento2-module`
* `mapp_digital/cloud`

The namespace of the module has been kept as `mapp_digital/cloud` with the mappconnect module being merged into it.

## Contents of Module Usage

1. [Configuration](#configuration-of-the-module)
   1. [Mapp Intelligence](#mapp-intelligence)
   3. [Mapp Web Push Integration](#mapp-web-push-integration)
   4. [Mapp Logging](#mapp-logging)
   5. [Mapp Entity Exports For Products, Customers, And Transactions](#mapp-entity-exports-for-products-customers-and-transactions)

### Configuration of the Module
---

The configuration for the Mapp Cloud module is split into 8 sections. These include:
* `tagintegration` (Mapp Intelligence - Tag Integration)
* `mapp_gtm` (Mapp Intelligence - Google Tag Manager)
* `mapp_connect` (Mapp Engage (Connect) - General)
* `mapp_connect_messages` (Mapp Engage (Connect) - Emails)
* `mapp_web_push` (Mapp Web Push)
* `mapp_logging` (Logging)
* `mapp_exports` (CSV Exports)

---

#### Mapp Intelligence

**Stores -> Configuration -> Mapp Cloud -> Mapp Intelligence - Tag Integration**

This section is for the standard Mapp tag integration. The fields here should be used to populate the integration details
that are then used for the data on the shopfront

**Stores -> Configuration -> Mapp Cloud -> Mapp Intelligence - Google Tag Manager**

If you also need to load the data into / via the Google Tag Manager (GTM) then the details can be populated in here. All
events will then be triggered for the correct services

#### Mapp Engage via Connect

**Stores -> Configuration -> Mapp Cloud -> Mapp Engage (Connect) - General**

When integrating with Mapp Engage, you will need to configure all the details for the integration as well as the URL
being used for the implementation. This can all be done from this location.

This module has been updated to use the message queue in Magento. This new method is not, however, enabled by
default in order to prevent previous modules that have used this functionality from breaking. Instead, this can 
be enabled by changing the `Synchronisation method` from `Legacy` to `DB Trigger`. 

The difference of this implementation allows for a few advantages:
1. The update to Mapp is not send in the same request as an action is performed. For example, order creation
   1. Due to the previous implementation, if there was an issue with the connection between Mapp and Magento, it could
cause issues when trying to save either a customer, order, or newsletter update. The new implementation removes this
risk by decoupling the two events.
2. Performance is improved as multiple requests can be sent simultaneously.
   1. This also prevents pages from having to wait for the API request to have responded before loading it.
3. This works with both the DB and AMPQ configurations for the message queue.

**This new functionality depends on the [message queues in Magento](https://experienceleague.adobe.com/docs/commerce-operations/configuration-guide/message-queues/message-queue-framework.html?lang=en)
being setup and enabled for it to function.** It works with both DB and Rabbitmq message queues. No additional setup for
the message queues is required for the module to work, as long as it is configured and working for Magento in general.

We recommend enabling the new method of implementation after it has been tested and confirmed on your local instance if 
upgrading to a newer version of the module. If this is the first instillation of the module, it's recommended to start
with this `DB Trigger` mode being enabled from the offset.

There are a few different areas of the configuration that require the connection be active before the options will appear.
For example, `Mapp Engage Groups` will only show you the options available from Mapp if you have configured and 
confirmed the integration connection first.

You should see `Connection Successful` in green beside the `Connection Status` if all has connected properly.

**Stores -> Configuration -> Mapp Cloud -> Mapp Engage (Connect) - General**

This section is for configuration the email templates desired for specific events in Magento. The main thing to consider 
here is, like the previous section, the integration is required to be configured and connected for Mapp template options
to appear in the list for use. Otherwise, the only option will be the default Magento template.

#### Mapp Web Push Integration

**Stores -> Configuration -> Mapp Cloud -> Mapp Web Push**

This module allows for the integration of web push notifications from Mapp into Magento. For this to be achieved, two
files will be generated and provided to you for each integration. Within these two files it will contain all
the values you need to add into the configuration of the module.

The JS files, instead of being directly required on the server, are generated on the fly based off of the configuration
values in the admin. This allows you to configure each store with a different value if, for example, you have
push notifications for different languages that are required on each of your stores in Magento.

It is worth adding that the functionality includes the scripts required for the push notification permissions modal
to be triggered on every page load. To prevent the module from adding scripts onto every page load and impacting on
performance, the module adds a cookie after it has been successfully added. If this cookie is present, then it will
no longer attempt to add the scripts.

The conditions for the script to be loaded are:
1. It has not been loaded previously
2. The user is either:
   1. Logged in
   2. Has just completed an order

The cookie added to the browser is named `webpush_script_initiated`. If you are testing your configuration, you can
delete this cookie to allow the scripts to be run again, or try in incognito mode.

#### Mapp Logging

**Stores -> Configuration -> Mapp Cloud -> Logging**

Due to the nature of the module, there is a lot of different interactions with the Mapp services. This requires a 
connection with an external location and for data to be sent and received from that location. Typically, due to this,
finding out exactly what has been sent and received can be quite difficult. For that reason, we have built in a custom
logging tool that logs information into both the database, and a custom file (`/var/log/mapp_digital.log`).

The configuration above allows you to configure how something is logged, and how much verbosity is needed in the 
logs themselves. You can choose to only log to either file or the databse, or log to both.

When logging to the database is enabled, the logs are then displayed in the admin. These can be seen by navigating
to **System -> Mapp Digital Logs**.

This grid can be filtered for each column via the filters with the standard functionality of Magento available for
creating custom grid views and removing / showing any of the columns as you wish.

For a log to be written, it has to be both enabled, and within the scope of verbosity. It is recommended to have 
the `Logging Level` set to `Debug` if you need to see very detailed information about requests and responses to and from
Mapp, `info` if you want to see all acknowledgements of interactions between Mapp and Magento, or on `error` if you
only want issues (if they arise) to be written.

It's also worth noting that these DB logs are deleted after a period of days. This can be changed by altering the value
of the `Delete Logs After x Days` configuration option. This only cleans the database, it does not alter the file
based logs.

#### Mapp Entity Exports For Products, Customers, And Transactions

**Stores -> Configuration -> Mapp Cloud -> CSV Exports**

If you are in need of syncing your entities from Magento into Mapp's interface, you will need a CSV export from 
Magento in order to do so. The admin interface of the module allows for this. There are two options for the 
location of the export:
1. Directly onto the server (Local Filesystem Only)
   1. This method also adds configuration to choose where on the server it should be created
2. Into an SFTP location (SFTP)
   1. Configuration is available to define the SFTP connection and location thereafter where the file should be stored

Each export can be triggered manually in two ways:
1. Via the configuration buttons labelled `Queue Export Now` within each entity's section
2. Via a command on the server:
   1. `php bin/magento mapp:export:customers`                                
   2. `php bin/magento mapp:export:orders`                                  
   3. `php bin/magento mapp:export:products`

When run via the command, it will use the same configuration values set in the admin and, therefore, send the file to 
the same configured location (filesystem / SFTP)

**This functionality depends on the [message queues in Magento](https://experienceleague.adobe.com/docs/commerce-operations/configuration-guide/message-queues/message-queue-framework.html?lang=en)
being setup and enabled for it to function.** It works with both DB and Rabbitmq message queues. No additional setup for
the message queues is required for the module to work, as long as it is configured and working for Magento in general.
