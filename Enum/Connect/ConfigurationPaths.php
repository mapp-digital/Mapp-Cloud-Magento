<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Enum\Connect;

/**
 * @todo: Convert Previous Usage of Config Values via Helper/ConnectHelper.php To Use This Enum Instead
 */
enum ConfigurationPaths: string
{
    case XML_PATH_PRODUCT_SYNC_ENABLED = 'mapp_connect/export/product_enable';
    case XML_PATH_PRODUCT_SYNC_USE_CACHED_URLS = 'mapp_connect/export/product_image_cache_enable';
    case XML_PATH_PRODUCT_SYNC_GENERATE_CACHED_URLS = 'mapp_connect/export/product_image_generate_enable';

    case XML_PATH_SYNC_METHOD = 'mapp_connect/export/sync_method';
    case XML_PATH_ORDER_STATUS_EXPORT = 'mapp_connect/export/transaction_send_on_status';
    case XML_PATH_NEWSLETTER_RETRY_LIMIT = 'mapp_connect/export/newsletter_retry_max';
    case XML_PATH_ORDER_RETRY_LIMIT = 'mapp_connect/export/transaction_retry_max';
    case XML_PATH_EMAILS_ENABLED = 'mapp_connect_messages/general/enable';
}
