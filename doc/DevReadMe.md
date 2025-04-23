# Developer Documentation

## Intro

This Mapp Cloud module is a combination of two previous packages. These were:
* `mappconnect/magento2-module`
* `mapp_digital/cloud`

The namespace of the module has been kept as `mapp_digital/cloud` with the mappconnect module being merged into it.

There has been additional functionality added into the new module as well as the merging of the previous code. It's also
very important to note that the module now works with a minimum of PHP 8.1 with it having been refactored to use best
practices throughout. It, for example, uses `enum`s and `property promotion` amongst other things, resulting in the 
locked minimum version being PHP 8.1. 

Moving forward the module is to be maintained with this version of PHP for as long as it is receiving security updates. 
Any PHP 8.2 changes (and beyond) will be made with backwards compatibility in mind from thereon.

## Contents of Module Usage

1. [Configuration](#configuration-of-the-module)
2. [Classes and Locations Of Interest](#classes-and-locations-of-interest)

#### Configuration of the Module
---

The configuration for the Mapp Cloud module is split into 8 sections. These include:
* `tagintegration` (Mapp Intelligence - Tag Integration)
* `mapp_gtm` (Mapp Intelligence - Google Tag Manager)
* `mapp_connect` (Mapp Engage (Connect) - General)
* `mapp_connect_messages` (Mapp Engage (Connect) - Emails)
* `mapp_web_push` (Mapp Web Push)
* `mapp_logging` (Logging)
* `mapp_exports` (CSV Exports)

Most of the configuration to be found in the `etc/adminhtml/system.xml` file is self-explanatory. There are, however, a 
few options that require additional context.

##### Below are some examples of configuration that may need some additional context if using them.
---

**[`mapp_connect/export/sync_method`](../etc/config.xml#L28) - Synchronisation method**

The module has been updated to not use Magento plugin interceptors when trying to send updates to the API integration. 
This has now been moved to the message queue. These message queues are updated via a cron 
(`mappdigital_cloud_triggers_publish`) of which uses entries of two changelog tables to run through and publish
messages into the queue. 

It's important to note that this behaviour is defaulted to use the previous method of interceptors initially to prevent
issues when installing the new version of the module. When updating this sync_method to be the `DB Trigger`, it will
start using the new method of API synchronisation. 

Due to the new method requiring DB triggers to be created for orders and newsletters, these are created on save of the 
configuration option. How this is done can be seen in the class 
`MappDigital\Cloud\Model\Adminhtml\System\Config\Backend\Connect\TriggerManagement`. This class is also responsible
for removing DB triggers when returning to the legacy sync method as well.

There are currently no plans to remove the old sync method, but it is deprecated, however.

The message queue functionality works with both the `db` and `ampq` methods and uses whichever is configured in the 
websites deployment configuration.

**[`mapp_connect/export/product_image_cache_enable`](../etc/system.xml#L224) - Add Cached Product URLs to Sync**

When sending product data to Mapp, it requires that the full URL is sent including the domain of the store. For images,
this URL can be generated with or without the cached image being included. This means that it is possible to either
send the URL of the source image, or the resized and cached image within Magento.

Enabling this configuration will ensure that the cached images are sent to Mapp. This is advantageous as it removes the
issue of sending links to huge media files that may have been uploaded, sent to Mapp, and then shown in emails. If
images, for whatever reason, are not reliably obtained via the cached URL, this can be disabled.

**[`mapp_connect/export/product_image_generate_enable`](../etc/system.xml#L237) - Generate Resized Product Images during Sync**

If sending the cached images to Mapp, you may want to ensure that all images are generated before sending them. This can
be particularly useful when doing a full sync of the catalog into Mapp. If you enable this configuration, the image 
generation will happen for the required image sizes before syncing the product. It's worth noting that this will 
_significantly_ reduce performance of the sync as it is reading and writing files for every product. 

Consider enabling this option if (some need match, not all depending on the circumstance):
* The catalog is small
* The transaction rate of product updates is low
* The image cache isn't being removed
* You are running a full sync of the catalog (see command `php bin/magento mapp:sync:products` - `MappDigital\Cloud\Console\Command\SyncProductCatalog`)

This action is always performed via the message queue and as such helps significantly with performance.

**[`mapp_connect/export/transaction_retry_max`](../etc/system.xml#L254) - Max Order Request Retry Count** &
**[`mapp_connect/export/newsletter_retry_max`](../etc/system.xml#L267) - Max Newsletter Request Retry Count**

Both of the above settings are configured to work with the message queue. If that is enabled, you are able to configure
a retry count if there is a failure for whichever reason (connection to the API for example). This does not work with
the legacy method of integration as that sends via plugin interceptors, and not the message queue

**[`mapp_connect_messages/*/*`](../etc/system.xml#L311) - Connect Email Configuration** 

For API email templates to appear in the list, the API credentials first need to be configured within the 
`mapp_connect/integration/*` section. If the API cannot be reached then they will not be added into the source model 
for the options. This can be seen here: `MappDigital\Cloud\Model\Config\Backend\Template on line 35`

**[`mapp_web_push/firebase/*`](../etc/system.xml#L716) - Firebase** & 
**[`mapp_web_push/pixel/*`](../etc/system.xml#L798) - Pixel** 

Both of these sections contain configuration options of which are all retrieved from the supplied JS files via Mapp.
Some base configuration options have been added as defaults via the [config.xml](../etc/config.xml) file for those that
remain consistent or should not be altered. If these values need to be changed, it can't be guarunteed that the 
functionality will behave as it should so be sure to confirm the reasons for this with either Mapp or the contact at 
for the website's development to be sure before changing them

**[`mapp_web_push/mapp_logging/*`](../etc/system.xml#L869) - Logging** 

A combined DB and file logger has been added into the module. Throughout the code logs are created. If they are actually
written to file / db depends on if both are enabled, and the level of the logging that has been configured. If this 
logging becomes too noisy, it's recommended to decrease the verbosity of the logging until it is needed again.

The requests themselves are set to be on the `debug` level of verbosity. If these requests are required to be logged,
be sure to have it on this level of verbosity in the configuration

Logs can be configured to be cleaned after a certain number of days. The default value for this is `31` days, but this
can be reduced if the logging is particularly large, but required.

Logging is enabled by default and set to a severity of `2` enabling critical and error logs to appear in both locations.

It's also worth noting that viewing the logs requires the relevant ACL permissions for `MappDigital_Cloud::log_view`

#### Classes and Locations Of Interest
---

##### CSV Entity Export
`MappDigital\Cloud\Model\Export\Entity\ExportAbstract` &
`MappDigital\Cloud\Model\Export\Entity\Customer` &
`MappDigital\Cloud\Model\Export\Entity\Order` &
`MappDigital\Cloud\Model\Export\Entity\Product` - These classes are responsible for the CSV export of entities. These
exports are used to import data into Mapp but only contain the required data when doing so. If you need to extend the
data being added into the CSV, these classes would be the entry point to be able to do so

`MappDigital\Cloud\Model\Export\Client\FileSystem` & 
`MappDigital\Cloud\Model\Export\Client\Sftp`- Both of these classes handle the export of the CSV. Due to these exports 
being handled by the message queue, they are not served to the browser. Instead they are exported to the server or a
configured SFTP location.

`MappDigital\Cloud\Console\Command\ExportCustomers` &
`MappDigital\Cloud\Console\Command\ExportOrders` &
`MappDigital\Cloud\Console\Command\ExportProducts` - These three commands allow for the instant generation of CSV exports
if required.

##### Message Queue Setup And Use

`MappDigital\Cloud\Setup\Recurring` - This class is responsible for regenerating the changelog tables on a 
`setup:upgrade`. It also creates the DB Triggers responsible for inserting values into them. think of this functionality
as similar to how MView works in Magento.

`MappDigital\Cloud\Model\Connect\SubscriptionManager` - This is the entry point to the management of a few important
sections of functionality. In here the DB triggers are managed along with the changelog table generation. 

There is also some functionality around data construction for data arrays being sent to Mapp via the API. Methods have 
been created as public to allow for plugin interceptors in case of the need to alter the data before it is sent to Mapp's
API

It's also worth noting that the DB trigger for the order is configured to check for the status of the order to be
different to the old status _and_ the same as the value set for the 
`mapp_connect/export/transaction_send_on_status` configuration option

`MappDigital\Cloud\Model\QueueMessage\Trigger\ConsumeQueue` - This is where the published messages for newsletter and 
order updates are processed. If you need to debug the processing of the queue, this would be a good place to start

`MappDigital\Cloud\Model\QueueMessage\Exporter\ConsumeQueue` - This is where the published messages for CSV entity 
exports are consumed. These all hook into the same methods used for the exporter classes mentioned in the 
[CSV Entity Export](#csv-entity-export) section. This should mean that if you have added any plugin interceptors around
those methods, the changes will still be applied when being run from the message queue.

It's recommended to not use preferences where possible in Magento to change behaviour so try to stick to the 
interceptors if you need to make changes.

`MappDigital\Cloud\Model\Connect\Catalog\Product\Consumer` - Here is where the published messages for the product sync
are consumed. Data is added for all custom attributes, extension attributes, and baseline required fields for Mapp. If 
changes are needed to the product sync data, this would be the best place to start. 

It's worth noting that the URLs in the sync and the export should all include the full path to the file including the
domain, so if changes are needed, be sure to continue using them.

`MappDigital\Cloud\Console\Command\SyncProductCatalog` - This command allows for the full sync of the product catalog. 
It is recommended to use this when first setting up the module to ensure that all products are sent to Mapp as they 
should be


##### Configuration in the Module

`\MappDigital\Cloud\Enum\Connect\ConfigurationPaths` &
`\MappDigital\Cloud\Enum\GTM\ConfigurationPaths` &
`\MappDigital\Cloud\Enum\TagIntegration\ConfigurationPaths` - These enums contain the configuration paths for a lot of those that
are referenced throughout the code. These can be useful if in need of using the configuration for further use

`MappDigital\Cloud\Model\Config\Backend\Template` &
`MappDigital\Cloud\Model\Config\Backend\Group` - Both of these classes contain the connection and retrieval of data that 
in Mapp and then used in Magento. There is also the use of the `ping` function within them if you are in need
of testing connections are working / seeing if there is any useful information in the returned response if it's not
working

##### Combined File and DB Logger and Extending 

`MappDigital\Cloud\Logger\CombinedLogger` - This class is responsible to logging data to files and/or the DB. If you 
would like to extend this functionality, it would be recommended to add a plugin interceptor on the 
`MappDigital\Cloud\Model\Config\Source\LogLevel` class and then preference the `CombinedLogger` to add in your 
additional functions for verbosity as some have not been added into this class through not being needed.

##### Web Push

`MappDigital\Cloud\Controller\Index\Firebase` &
`MappDigital\Cloud\Controller\Index\Pixel` - These two controllers, when hit, render a JS snippet based on the 
configuration values set in the admin. It has been done this way so that many different files can be generated per
store view allowing for different integrations per store (and, therefore, translations et. al).

These classes then use both `MappDigital\Cloud\Block\FirebaseData` and `MappDigital\Cloud\Block\PixelData` to render
out the content based on each of their own custom layout handle (these can be seen in `view/frontend/layout/*`)

`MappDigital\Cloud\Block\Webpush\Initialise` - This class is responsible for adding the required JS to the page in order
to prompt the user to accept the ability for web push notifications to be sent. It's worth noting that this class
includes a cookie check. If it finds a cookie, it means that the init has already happened and, therefore, avoids 
continually loading JS assets onto the page on every page load

##### Intelligence Integration

`MappDigital\Cloud\Helper\TrackingScript` - This class is responsible for generating the JS for the page that is 
required for the intelligence cart and wishlist behaviours.

This is also where the extension of the ti JS object is merged via an AJAX call to a controller (See below)

`MappDigital\Cloud\Controller\Data\Get` - This controller is used for the AJAX requests to gather all contextual
information that is then added/merged into the JS ti object onto the page. This is then can be used for datalayers
from thereon.

`MappDigital\Cloud\Model\Datalayer` - If the datalayer needs to be extended with additional information, you can
use this class and the `addVariable` function within it. When the datalayer is returned, it loops through all variables
and then returns them from there via the controller in the previous entry above. This should mean that, programmatically, 
there are no limitations to what can be added into the datalayer for referencing.

`MappDigital\Cloud\Model\Data\AbstractData` - This class should be extended if adding a new / custom entity into the 
datalayer. This is because it replaces how the dataobject functions to allow for the get and set functionality to 
works as needed. 

##### Connect (Engage)

`MappDigital\Cloud\Model\NewsletterSubscriber` - This class is responsible for allowing API requests into Magento to 
alter newsletter subscriptions, something that is not available out of the box.

`MappDigital\Cloud\Plugin\SubscriberPlugin` &
`MappDigital\Cloud\Plugin\SubscriptionManagerPlugin` - This is the legacy implementation for newsletter updates. These
methods are called every time there is an update and all depend on an API connection to function. They do not
benefit from the retry functionality built into the message queue as a result

Both of these classes use the same entry point into the api submissions, however, by going via the 
`MappDigital\Cloud\Model\Connect\SubscriptionManager` class
