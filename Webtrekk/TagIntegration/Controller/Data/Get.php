<?php
/**
 * @author Webtrekk Team
 * @copyright Copyright (c) 2016 Webtrekk GmbH (https://www.webtrekk.com)
 * @package Webtrekk_TagIntegration
 */

namespace Webtrekk\TagIntegration\Controller\Data;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Webtrekk\TagIntegration\Helper\Data;
use Webtrekk\TagIntegration\Helper\DataLayer as DataLayerHelper;
use Webtrekk\TagIntegration\Model\DataLayer;

class Get implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Http
     */
    protected $_request;

    /**
     * @var Data
     */
    protected $_tiHelper;

    /**
     * @var DataLayer
     */
    protected $_dataLayerModel;

    /**
     * @var DataLayerHelper
     */
    protected $_dataLayerHelper;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param Http $request
     * @param Data $tiHelper
     * @param DataLayer $dataLayer
     * @param DataLayerHelper $dataLayerHelper
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Http $request,
        Data $tiHelper,
        DataLayer $dataLayer,
        DataLayerHelper $dataLayerHelper
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_request = $request;
        $this->_tiHelper = $tiHelper;
        $this->_dataLayerModel = $dataLayer;
        $this->_dataLayerHelper = $dataLayerHelper;
    }

    /**
     * JSON tiConfig and dataLayer with non-cachable data, or just productAdd info
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $params = $this->_request->getParams();
        $isAddToCart = isset($params['add']);
        if(!$isAddToCart) {
            if (isset($params['product'])) {
                $this->_dataLayerModel->setProductDataLayer();
            }
            $this->_dataLayerModel->setCustomerDataLayer();
            $this->_dataLayerModel->setOrderDataLayer();
        }
        $this->_dataLayerModel->setCartDataLayer();
        $dataLayer  = $this->_dataLayerHelper->mappify($this->_dataLayerModel->getVariables());
        $dataLayer = $this->_tiHelper->removeParameterByBlacklist($dataLayer);
        $data = [
                "eventName" => $isAddToCart ? $this->_tiHelper->getAddToCartEventName() : null,
                "dataLayer" => $dataLayer
        ];
        if(!$isAddToCart) {
            $data['config'] = $this->_tiHelper->getTagIntegrationConfig();
        }
        return $this->resultJsonFactory->create()->setData($data);
    }
}
