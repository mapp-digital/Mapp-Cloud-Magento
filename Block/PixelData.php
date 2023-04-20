<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Block;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Model\Pixel;

/**
 * Firebase Block Class.
 *
 * Prepares base content for pixel-webpush.min.js and implements Page Cache functionality.
 */
class PixelData extends AbstractBlock implements IdentityInterface
{
    public function __construct(
        protected Pixel $pixelData,
        protected CombinedLogger $mappCombinedLogger,
        protected StoreManagerInterface $storeManager,
        protected Dir $magentoModuleDirectory,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Retrieve base content for pixel-webpush.min.js file
     *
     * @return string
     */
    protected function _toHtml()
    {
        try {
            if ($this->_scopeConfig->getValue('mapp_web_push/general/enable', ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getId())) {
                return $this->configToHtml() . PHP_EOL;
            }
        } catch (Exception $exception) {
            $this->mappCombinedLogger->error('Error when trying to generate Pixel JS file: ' . $exception->getMessage(), __CLASS__, __FUNCTION__, ['error' => $exception->getMessage()]);
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
        $pixelData = $this->pixelData->getPixelData();
        $firebaseData = $this->pixelData->getFirebaseData();

        $pixelJsFilePartsPath = $this->magentoModuleDirectory->getDir('MappDigital_Cloud','view') . '/public/js/';
        $pixelMinJs = \file_get_contents($pixelJsFilePartsPath . 'pixel-part-2.js');

        return <<<JS
(function (window) {

    setTimeout(function(){
    var wt_webpushConfig = window.wt_webpushConfig || {
        serviceURL: '{$pixelData['serviceURL']}',
        webpushScriptIncluded: {$pixelData['webpushScriptIncluded']},
        useUserMatching: {$pixelData['useUserMatching']},
        xKey: '{$pixelData['xKey']}',
        serviceWorkerScript: '{$pixelData['serviceWorkerScript']}',
        includeFirebaseScripts: {$pixelData['includeFirebaseScripts']},
        firebaseConfig: {
            apiKey: '{$firebaseData['apiKey']}',
            authDomain: '{$firebaseData['authDomain']}',
            projectId: '{$firebaseData['projectId']}',
            storageBucket: '{$firebaseData['storageBucket']}',
            messagingSenderId: '{$firebaseData['messagingSenderId']}',
            appId: '{$firebaseData['appId']}',
            measurementId: '{$firebaseData['measurementId']}'
        }
    };

        {$pixelMinJs}
}, 2000);
})
(window);

JS;
    }

    /**
     * Get unique page cache identities
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getIdentities()
    {
        return [
            'pixel_webpush_' . $this->storeManager->getStore()->getId(),
        ];
    }
}
