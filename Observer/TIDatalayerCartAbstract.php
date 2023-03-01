<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Observer;

use Magento\Catalog\Model\Product as MagentoProductModel;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManager;
use MappDigital\Cloud\Helper\Config;
use MappDigital\Cloud\Helper\DataLayer as DataLayerHelper;
use MappDigital\Cloud\Model\Data\Product as MappProductModel;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\App\Request\Http;

abstract class TIDatalayerCartAbstract implements ObserverInterface
{
    protected Session $checkoutSession;
    protected Config $config;
    protected MappProductModel $mappProductModel;
    protected ProductAttributeRepositoryInterface $productAttributeRepositoryInterface;
    protected Http $request;

    /**
     * @param Session $checkoutSession
     * @param Config $config
     * @param MappProductModel $mappProductModel
     * @param ProductAttributeRepositoryInterface $productAttributeRepositoryInterface
     * @param Http $request
     */
    public function __construct(
        Session $checkoutSession,
        Config $config,
        MappProductModel $mappProductModel,
        ProductAttributeRepositoryInterface $productAttributeRepositoryInterface,
        Http $request
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->mappProductModel = $mappProductModel;
        $this->productAttributeRepositoryInterface = $productAttributeRepositoryInterface;
        $this->request = $request;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isEnabled()) {
            if (($product = $this->mappProductModel->getProduct()) && $product->hasData()) {
                $this->checkoutSession->setData('webtrekk_addproduct', DataLayerHelper::merge($this->getSessionData('webtrekk_addproduct'), $this->getProductData()));
            }
        }
    }

    /**
     * @param Item $item
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getProductData(Item $item)
    {
        if ($this->config->isEnabled()) {
            if (($product = $this->mappProductModel->getProduct()) && $product->hasData()) {
                try {
                    $urlFragment = DataLayerHelper::getUrlFragment($product);
                    $productData = $this->mappProductModel->getDataLayer($urlFragment);
                    $productData['qty'] = intval($item->getQtyToAdd());
                    $productData['quantity'] = intval($item->getQtyToAdd());
                    $productData['status'] = 'add';
                    $allAttributesForProduct = [];

                    if($item->getProductType() === 'configurable') {
                        $selectedOptions = json_decode($item->getOptionsByCode()['attributes']->getValue(), true);
                        foreach ($selectedOptions as $attributeCodeId => $optionValueId) {
                            $attributeRepo = $this->productAttributeRepositoryInterface->get($attributeCodeId);
                            $allAttributesForProduct[$attributeRepo->getAttributeCode()] = $attributeRepo->getSource()->getOptionText($optionValueId);
                        }
                    }

                    $productData['attributes'] = $allAttributesForProduct;
                    $productData['price'] = $item->getPrice();
                    $productData['cost'] = $item->getPrice();
                    $productData['sku'] = $item->getSku();
                    $productData['name'] = $item->getName();
                    $productData['weight'] = $item->getWeight();

                    try {
                        $productData['currency'] = $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
                    } catch (NoSuchEntityException $exception) {}

                    return $productData;
                } catch (NoSuchEntityException $exception) {
                    return [];
                }
            }
        }

        return [];
    }

    /**
     * @param string $key
     * @return array
     */
    protected function getSessionData(string $key): array
    {
        return $this->checkoutSession->getData($key) ?? [];
    }
}
