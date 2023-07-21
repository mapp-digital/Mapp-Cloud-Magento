<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Enum\GTM;

enum ConfigurationPaths : string
{
    case XML_PATH_GTM_ENABLE = 'mapp_gtm/general/gtm_enable';
    case XML_PATH_GTM_LOAD = 'mapp_gtm/general/gtm_load';
    case XML_PATH_GTM_ID = 'mapp_gtm/general/gtm_id';
    case XML_PATH_GTM_DATALAYER = 'mapp_gtm/general/gtm_datalayer';
    case XML_PATH_GTM_TRIGGER_BASKET = 'mapp_gtm/general/gtm_trigger_basket';
    case XML_PATH_GTM_ADD_TO_CART_EVENTNAME = 'mapp_gtm/general/gtm_add_to_cart_eventname';
}
