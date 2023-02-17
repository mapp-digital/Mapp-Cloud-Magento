<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
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
    private PixelDataBlock $pixelDataBlock;
    private FirebaseDataBlock $fireBaseDataBlock;
    private StoreManagerInterface $storeManager;
    private CustomerSession $customerSession;
    private AssetRepository $assetRepository;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        PixelDataBlock $pixelDataBlock,
        FirebaseDataBlock $fireBaseDataBlock,
        AssetRepository $assetRepository,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->pixelDataBlock = $pixelDataBlock;
        $this->fireBaseDataBlock = $fireBaseDataBlock;
        $this->assetRepository = $assetRepository;

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
