<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Block;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use MappDigital\Cloud\Block\PixelData as PixelDataBlock;
use MappDigital\Cloud\Block\FirebaseData as FirebaseDataBlock;

/**
 * Firebase Block Class.
 *
 * Prepares base content for pixel-webpush.min.js and implements Page Cache functionality.
 */
class Pixel extends Template implements IdentityInterface
{
    public function __construct(
        protected StoreManagerInterface $storeManager,
        protected CustomerSession $customerSession,
        protected PixelDataBlock $pixelDataBlock,
        protected FirebaseDataBlock $fireBaseDataBlock,
        protected AssetRepository $assetRepository,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getJsOutputPixel(): string
    {
        return $this->pixelDataBlock->configToHtml();
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getJsOutputFirebase(): string
    {
        return $this->fireBaseDataBlock->configToHtml();
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
            'pixel_webpush_' . $this->storeManager->getStore()->getId() . '_js_config',
            $this->customerSession->getCustomerId() ?? 'guest'
        ];
    }
}
