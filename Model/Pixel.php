<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;

/**
 * Returns data for pixel-webpush.min.js file
 */
class Pixel
{
    const XML_CONFIG_PATH_SERVICE_URL = 'mapp_web_push/pixel/service_url';
    const XML_CONFIG_PATH_WEBPUSH_SCRIPT_INCLUDED = 'mapp_web_push/pixel/webpush_script_included';
    const XML_CONFIG_PATH_USE_USER_MATCHING = 'mapp_web_push/pixel/use_user_matching';
    const XML_CONFIG_PATH_X_KEY = 'mapp_web_push/pixel/x_key';
    const XML_CONFIG_PATH_SERVICE_WORKER_SCRIPT = 'mapp_web_push/pixel/service_worker_script';
    const XML_CONFIG_PATH_INCLUDE_FIREBASE_SCRIPTS = 'mapp_web_push/pixel/include_firebase_scripts';

    const ALL_PIXEL_XML_CONFIG_PATHS = [
        'serviceURL' => self::XML_CONFIG_PATH_SERVICE_URL,
        'webpushScriptIncluded' => self::XML_CONFIG_PATH_WEBPUSH_SCRIPT_INCLUDED,
        'useUserMatching' => self::XML_CONFIG_PATH_USE_USER_MATCHING,
        'xKey' => self::XML_CONFIG_PATH_X_KEY,
        'serviceWorkerScript' => self::XML_CONFIG_PATH_SERVICE_WORKER_SCRIPT,
        'includeFirebaseScripts' => self::XML_CONFIG_PATH_INCLUDE_FIREBASE_SCRIPTS
    ];

    private Firebase $firebase;
    private ScopeConfigInterface $scopeConfig;
    private StoreManager $storeManager;

    public function __construct(
        Firebase $firebase,
        ScopeConfigInterface $scopeConfig,
        StoreManager $storeManager
    ) {
        $this->firebase = $firebase;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Get the main data for pixel-webpush.min.js file as defined in configuration
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getPixelData(): array
    {
        foreach (self::ALL_PIXEL_XML_CONFIG_PATHS as $key => $path) {
            $data[$key] = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()->getId()
            );
        }

        return $data ?? [];
    }

    /**
     * Get the main data for firebase-messaging-sw.js file as defined in configuration
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getFirebaseData(): array
    {
        return $this->firebase->getData() ?? [];
    }
}
