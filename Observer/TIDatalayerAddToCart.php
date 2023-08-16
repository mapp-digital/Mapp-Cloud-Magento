<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Observer;

use Magento\Catalog\Model\Product as MagentoProductModel;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use MappDigital\Cloud\Helper\DataLayer as DataLayerHelper;

class TIDatalayerAddToCart extends TIDatalayerCartAbstract
{
    /**
     * @param Observer $observer
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isEnabled()) {
            $item = $observer->getEvent()->getData('quote_item');
            /** @var MagentoProductModel $product */
            $product = $observer->getEvent()->getData('product');

            if ($product->hasData()) {
                $this->mappProductModel->setProduct($product);

                $this->checkoutSession->setData(
                    'webtrekk_addproduct',
                    DataLayerHelper::merge(
                        $this->getSessionData('webtrekk_addproduct'),
                        $this->getProductData($item)
                    )
                );
            }
        }
    }
}
