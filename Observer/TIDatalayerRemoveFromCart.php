<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;
use MappDigital\Cloud\Helper\DataLayer as DataLayerHelper;

class TIDatalayerRemoveFromCart extends TIDatalayerCartAbstract
{
    /**
     * @param Observer $observer
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isEnabled()) {
            /** @var Item $item */
            $item = $observer->getEvent()->getData('quote_item');

            if ($item->getProduct()->hasData()) {
                $this->mappProductModel->setProduct($item->getProduct());

                $this->checkoutSession->setData(
                    'webtrekk_removeproduct',
                    DataLayerHelper::merge(
                        $this->getSessionData('webtrekk_removeproduct'),
                        $this->getProductData($item)
                    )
                );
            }
        }
    }
}
