<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */

namespace MappDigital\Cloud\Controller\Data;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use MappDigital\Cloud\Helper\Config;
use MappDigital\Cloud\Helper\DataLayer as DataLayerHelper;
use MappDigital\Cloud\Model\DataLayer as DataLayerModel;

class Get implements HttpGetActionInterface
{
    protected JsonFactory $resultJsonFactory;
    protected Http $request;
    protected Config $config;
    protected DataLayerModel $dataLayerModel;
    protected DataLayerHelper $dataLayerHelper;

    public function __construct(
        JsonFactory $resultJsonFactory,
        Http $request,
        Config $config,
        DataLayerModel $dataLayer,
        DataLayerHelper $dataLayerHelper
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->config = $config;
        $this->dataLayerModel = $dataLayer;
        $this->dataLayerHelper = $dataLayerHelper;
    }

    /**
     * JSON tiConfig and dataLayer with non-cachable data, or just productAdd info
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $isAddToCart = isset($params['add']);
        $isRemoveFromCart = isset($params['remove']);

        if (!$isAddToCart && !$isRemoveFromCart) {
            if (isset($params['product'])) {
                $this->dataLayerModel->setProductDataLayer($params['product']);
            }
            $this->dataLayerModel->setCustomerDataLayer();
            $this->dataLayerModel->setOrderDataLayer();
            $this->dataLayerModel->setWishlistData();
        }

        $this->dataLayerModel->setCartDataLayer();
        $dataLayer = $this->dataLayerHelper->mappify($this->dataLayerModel->getVariables());
        $dataLayer = $this->config->removeParameterByBlacklist($dataLayer);

        $data = [
            "eventName" => $this->config->getAddToCartEventName(),
            "eventNameRemove" => $this->config->getRemoveFromCartEventName(),
            "dataLayer" => $dataLayer,
            "config" => $this->config->getConfig()
        ];

        if ($isAddToCart || $isRemoveFromCart) {
            $data = $this->addCartActionData($data, $dataLayer);
        }

        if (isset($dataLayer['addWishlistProductId'])) {
            $data = $this->addWishlistData($data, $dataLayer);
        }

        return $this->resultJsonFactory->create()->setData($data);
    }

    /**
     * @return string
     */
    private function getCartAction(): string
    {
        $params = $this->request->getParams();

        if (isset($params['add'])) {
            return 'addToCartMapp';
        }

        if (isset($params['remove'])) {
            return 'removeFromCartMapp';
        }

        return '';
    }

    /**
     * @param array $data
     * @param array $dataLayer
     * @return array
     */
    private function addCartActionData(array $data, array $dataLayer): array
    {
        $data[$this->getCartAction()] = [
            "product" => [
                "id" => $dataLayer['addProductId'] ?? "",
                "name" => $dataLayer['addProductName'] ?? "",
                "sku" => $dataLayer['addProductSku'] ?? "",
                "price" => $dataLayer['addProductPrice'] ?? "",
                "specialPrice" => $dataLayer['addProductSpecialPrice'] ?? "",
                "qty" => $dataLayer['addProductQty'] ?? "",
                "category" => implode(' ; ',$dataLayer['addProductCategories'] ?? []),
                "url" => $dataLayer['addProductUrlKey'] ?? "",
                "img" => $dataLayer['addProductImage'] ?? "",
                "currency" => $dataLayer['addProductCurrency'] ?? ""
            ]
        ];

        return $data;
    }

    /**
     * @param array $data
     * @param array $dataLayer
     * @return array
     */
    private function addWishlistData(array $data, array $dataLayer): array
    {
        $data['addToWishlistMapp'] = [
            "product" => [
                "id" => $dataLayer['addWishlistProductId'] ?? "",
                "name" => $dataLayer['addWishlistProductName'] ?? "",
                "sku" => $dataLayer['addWishlistProductSku'] ?? "",
                "price" => $dataLayer['addWishlistProductPrice'] ?? "",
                "qty" => $dataLayer['addWishlistQty'] ?? "",
                "currency" => $dataLayer['addWishlistCurrency'] ?? "",
            ]
        ];

        return $data;
    }
}
