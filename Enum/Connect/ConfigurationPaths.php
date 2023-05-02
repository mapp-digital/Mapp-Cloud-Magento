<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Enum\Connect;

/**
 * @todo: Convert Previous Usage of Config Values To Use This Enum Instead
 */
enum ConfigurationPaths: string
{
    case XML_PATH_PRODUCT_SYNC_ENABLED = 'mapp_connect/export/product_enable';
    case XML_PATH_PRODUCT_SYNC_USE_CACHED_URLS = 'mapp_connect/export/product_image_cache_enable';
    case XML_PATH_PRODUCT_SYNC_GENERATE_CACHED_URLS = 'mapp_connect/export/product_image_generate_enable';
}
