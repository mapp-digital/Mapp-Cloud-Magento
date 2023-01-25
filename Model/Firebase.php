<?php
namespace MappDigital\Cloud\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;

/**
 * Returns data for firebase-messaging-sw.js file
 */
class Firebase
{
    const XML_CONFIG_PATH_FIREBASE_VERSION = 'mapp_web_push/firebase/firebase_version';
    const XML_CONFIG_PATH_API_KEY = 'mapp_web_push/firebase/api_key';
    const XML_CONFIG_PATH_AUTH_DOMAIN = 'mapp_web_push/firebase/auth_domain';
    const XML_CONFIG_PATH_PROJECT_ID = 'mapp_web_push/firebase/project_id';
    const XML_CONFIG_PATH_STORAGE_BUCKET = 'mapp_web_push/firebase/storage_bucket';
    const XML_CONFIG_PATH_MESSAGE_SENDER_ID = 'mapp_web_push/firebase/message_sender_id';
    const XML_CONFIG_PATH_APP_ID = 'mapp_web_push/firebase/app_id';
    const XML_CONFIG_PATH_MEASUREMENT_ID = 'mapp_web_push/firebase/measurement_id';

    const ALL_FIREBASE_XML_CONFIG_PATHS = [
        'firebaseVersion' => self::XML_CONFIG_PATH_FIREBASE_VERSION,
        'apiKey' => self::XML_CONFIG_PATH_API_KEY,
        'authDomain' => self::XML_CONFIG_PATH_AUTH_DOMAIN,
        'projectId' => self::XML_CONFIG_PATH_PROJECT_ID,
        'storageBucket' => self::XML_CONFIG_PATH_STORAGE_BUCKET,
        'messagingSenderId' => self::XML_CONFIG_PATH_MESSAGE_SENDER_ID,
        'appId' => self::XML_CONFIG_PATH_APP_ID,
        'measurementId' => self::XML_CONFIG_PATH_MEASUREMENT_ID,
    ];

    private ScopeConfigInterface $scopeConfig;
    private StoreManager $storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManager $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Get the main data for firebase-messaging-sw.js file as defined in configuration
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(): array
    {
        foreach (self::ALL_FIREBASE_XML_CONFIG_PATHS as $key => $path) {
            $data[$key] = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()->getId()
            );
        }

        return $data ?? [];
    }
}
