<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Block;

use Exception;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Model\Firebase;

/**
 * Firebase Block Class.
 *
 * Prepares base content for firebase-messaging-sw.js and implements Page Cache functionality.
 */
class FirebaseData extends AbstractBlock implements IdentityInterface
{
    public function __construct(
        private Firebase $firebase,
        private CombinedLogger $mappCombinedLogger,
        private StoreManagerInterface $storeManager,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Retrieve base content for firebase-messaging-sw.js file
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        try {
            if ($this->_scopeConfig->getValue('mapp_web_push/general/enable', ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getId())) {
                return $this->configToHtml() . PHP_EOL;
            }
        } catch (Exception $exception) {
            $this->mappCombinedLogger->error('Error when trying to generate Firebase JS file: ' . $exception->getMessage(), __CLASS__, __FUNCTION__, ['error' => $exception->getMessage()]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
        }

        return parent::_toHtml();
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function configToHtml(): string
    {
        $jsData = $this->firebase->getData();

        return <<<JS
if('function' === typeof importScripts) {
    const firebaseVersion = '{$jsData['firebaseVersion']}';

    importScripts("https://www.gstatic.com/firebasejs/" + firebaseVersion + "/firebase-app.js");
    importScripts("https://www.gstatic.com/firebasejs/" + firebaseVersion + "/firebase-messaging.js");
    addEventListener('message', onMessage);

    function onMessage(e) {}

    const firebaseConfig = {
        apiKey: "{$jsData['apiKey']}",
        authDomain: "{$jsData['authDomain']}",
        projectId: "{$jsData['projectId']}",
        storageBucket: "{$jsData['storageBucket']}",
        messagingSenderId: "{$jsData['messagingSenderId']}",
        appId: "{$jsData['appId']}",
        measurementId: "{$jsData['measurementId']}"
    };

    firebase.initializeApp(firebaseConfig);

    const messaging = firebase.messaging();

    messaging.onBackgroundMessage(function(payload) {});
}
JS;
    }

    /**
     * Get unique page cache identities
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getIdentities(): array
    {
        return [
            'firebase_' . $this->storeManager->getStore()->getId(),
        ];
    }
}
