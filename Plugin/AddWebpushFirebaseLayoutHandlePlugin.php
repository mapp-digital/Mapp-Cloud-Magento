<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */

declare(strict_types=1);

namespace MappDigital\Cloud\Plugin;

use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\View\Result\Layout;

/**
 * Plugin to add Adobe ims layout handle when module is active
 */
class AddWebpushFirebaseLayoutHandlePlugin
{
    const COOKIE_NAME_WEBPUSH_SET = 'webpush_script_initiated';
    const COOKIE_NAME_POSITIVE_VALUE = 'initialised';
    const WEBPUSH_JS_HANDLE = 'webpush_initialise_scripts';

    public function __construct(
        private CookieManagerInterface $cookieManager
    ) {}

    /**
     * Add handle only when user hasn't had it initialised to avoid needlessly loading JS onto the page
     *
     * @param Layout $subject
     * @param Layout $result
     * @return Layout
     */
    public function afterAddDefaultHandle(Layout $subject, Layout $result): Layout
    {
        if ($this->cookieManager->getCookie(self::COOKIE_NAME_WEBPUSH_SET)) {
            return $result;
        }

        $result->addHandle(self::WEBPUSH_JS_HANDLE);
        return $result;
    }
}
