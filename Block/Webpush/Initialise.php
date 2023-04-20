<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Block\Webpush;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Stdlib\Cookie\CookieMetadata;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Plugin\AddWebpushFirebaseLayoutHandlePlugin;
use Magento\Framework\Session\Config\ConfigInterface as SessionConfigInterface;

class Initialise extends Template
{
    const FIREBASE_APP_JS_PATH = 'MappDigital_Cloud::js/firebase-app.min.js';
    const FIREBASE_MESSAGING_JS_PATH = 'MappDigital_Cloud::js/firebase-messaging.min.js';

    public function __construct(
        protected CustomerSession $customerSession,
        protected CheckoutSession $checkoutSession,
        protected CookieManagerInterface $cookieManager,
        protected CookieMetadataFactory $cookieMetadataFactory,
        protected SessionConfigInterface $sessionConfig,
        protected AssetRepository $assetRepository,
        protected CombinedLogger $mappCombinedLogger,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->cookieManager->getCookie(AddWebpushFirebaseLayoutHandlePlugin::COOKIE_NAME_WEBPUSH_SET)) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @param $html
     * @return string
     */
    protected function _afterToHtml($html)
    {
        try {
            if ($this->canInitJsFiles() && !$this->cookieManager->getCookie(AddWebpushFirebaseLayoutHandlePlugin::COOKIE_NAME_WEBPUSH_SET)) {
                $sensitiveCookMetadata = $this->cookieMetadataFactory->createSensitiveCookieMetadata(
                    [
                        CookieMetadata::KEY_DURATION => $this->sessionConfig->getCookieLifetime()
                    ]
                )->setPath('/');

                $this->cookieManager->setSensitiveCookie(
                    AddWebpushFirebaseLayoutHandlePlugin::COOKIE_NAME_WEBPUSH_SET,
                    AddWebpushFirebaseLayoutHandlePlugin::COOKIE_NAME_POSITIVE_VALUE,
                    $sensitiveCookMetadata
                );
            }
        } catch (\Exception $exception) {
            $this->mappCombinedLogger->error('Error Trying to initialise assets for webpush functionality: ' . $exception->getMessage(), __CLASS__, __FUNCTION__);
        }

        return parent::_afterToHtml($html);
    }

    /**
     * @return string
     */
    public function getUserConfirmJs(): string
    {
        return <<<JS
window.mappWebpushMessage = [];
window.mappWebpushMessage.push({
	action: 'alias',
	value: '{$this->getAlias()}'
});
JS;
    }

    /**
     * @return bool
     */
    public function canInitJsFiles(): bool
    {
        if ($this->customerSession->isLoggedIn() || $this->checkoutSession->getLastRealOrder()->getId()) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getPageAssets(): array
    {
        try {
            return [
                $this->assetRepository->createAsset(self::FIREBASE_APP_JS_PATH)->getSourceUrl(),
                $this->assetRepository->createAsset(self::FIREBASE_MESSAGING_JS_PATH)->getSourceUrl(),
                '/firebase-messaging-sw.js',
                '/pixel-webpush.min.js'
            ];
        } catch (\Exception $exception) {
            $this->mappCombinedLogger->error('Error Trying to initialise assets for webpush functionality: ' . $exception->getMessage(), __CLASS__, __FUNCTION__);
        }
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomer()->getEmail();
        }

        if (($order = $this->checkoutSession->getLastRealOrder()) && $order->getCustomerEmail()) {
            return $order->getCustomerEmail();
        }

        return '';
    }
}
