<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2021 Mapp Digital US, LLC (https://www.mapp.com)
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

        if (!$isAddToCart) {
            if (isset($params['product'])) {
                $this->dataLayerModel->setProductDataLayer($params['product']);
            }
            $this->dataLayerModel->setCustomerDataLayer();
            $this->dataLayerModel->setOrderDataLayer();
        }

        $this->dataLayerModel->setCartDataLayer();
        $dataLayer = $this->dataLayerHelper->mappify($this->dataLayerModel->getVariables());
        $dataLayer = $this->config->removeParameterByBlacklist($dataLayer);

        $data = [
            "eventName" => $this->config->getAddToCartEventName(),
            "dataLayer" => $dataLayer,
            "config" => $this->config->getConfig(),
        ];

        return $this->resultJsonFactory->create()->setData($data);
    }
}
