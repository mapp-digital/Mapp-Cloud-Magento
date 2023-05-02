<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Enum\TagIntegration;

enum ConfigurationPaths : string
{
    case XML_PATH_ENABLE = 'tagintegration/general/enable';
    case XML_PATH_TAGINTEGRATION_ID = 'tagintegration/general/tagintegration_id';
    case XML_PATH_TAGINTEGRATION_DOMAIN = 'tagintegration/general/tagintegration_domain';
    case XML_PATH_CUSTOM_DOMAIN = 'tagintegration/general/custom_domain';
    case XML_PATH_CUSTOM_PATH = 'tagintegration/general/custom_path';
    case XML_PATH_ATTRIBUTE_BLACKLIST = 'tagintegration/general/attribute_blacklist';
    case XML_PATH_ADD_TO_CART_EVENT_NAME = 'tagintegration/general/add_to_cart_event_name';
    case XML_PATH_REMOVE_FROM_CART_EVENT_NAME = 'tagintegration/general/remove_from_cart_event_name';
}
