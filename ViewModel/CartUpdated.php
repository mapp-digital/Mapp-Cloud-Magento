<?php

namespace MappDigital\Cloud\ViewModel;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use MappDigital\Cloud\Helper\Config;
use MappDigital\Cloud\Helper\DataLayer as DataLayerHelper;
use MappDigital\Cloud\Model\DataLayer as DataLayerModel;

class CartUpdated implements ArgumentInterface
{
    public function __construct(
        private Session $checkoutSession,
        private Config $config,
        private DataLayerModel $dataLayerModel,
        private DataLayerHelper $dataLayerHelper,
    ) {
    }

    public function getConfig(): ?array
    {
        $removeProduct = $this->checkoutSession->getData('webtrekk_removeproduct');
        $addProduct = $this->checkoutSession->getData('webtrekk_addproduct');
        $product = $addProduct ?? $removeProduct;
        if (!$product) {
            return null;
        }
        $this->dataLayerModel->setCartDataLayer();
        $dataLayer = $this->dataLayerHelper->mappify($this->dataLayerModel->getVariables());
        $dataLayer = $this->config->removeParameterByBlacklist($dataLayer);

        $productData = [
            "id" => $product['entity_id'] ?? "",
            "name" => $product['name'] ?? "",
            "sku" => $product['sku'] ?? "",
            "price" => $product['price'] ?? "",
            "specialPrice" => $product['special_price'] ?? "",
            "qty" => $product['qty'] ?? "",
            "category" => implode(' ; ', $product['category_ids'] ?? []),
            "url" => $product['url_key'] ?? "",
            "img" => $product['image'] ?? "",
            "currency" => $product['currency'] ?? ""
        ];

        $data = [
            'config' => $this->config->getConfig(),
            'event' => $addProduct ? $this->config->getAddToCartEventName() : $this->config->getRemoveFromCartEventName(),
            'addToCartEventName' => $this->config->getAddToCartEventName(),
            'removeFromCartEventName' => $this->config->getRemoveFromCartEventName(),
            'productAddDataLayer' => $dataLayer,
        ];
        if ($addProduct) {
            $data['productAddToCartMapp'] = $productData;
        }
        if ($removeProduct) {
            $data['productRemoveFromCartMapp'] = $productData;
        }

        return $data;
    }
}
