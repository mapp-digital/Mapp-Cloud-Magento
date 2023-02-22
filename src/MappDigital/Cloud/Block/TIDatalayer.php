<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2021 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use MappDigital\Cloud\Helper\Config;
use MappDigital\Cloud\Helper\TrackingScript;
use MappDigital\Cloud\Helper\DataLayer as DataLayerHelper;
use MappDigital\Cloud\Model\DataLayer;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Helper\Data as Catalog;

class TIDatalayer extends Template
{

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var DataLayerHelper
     */
    protected $_dataLayerHelper;

    /**
     * @var DataLayer
     */
    protected $_dataLayerModel = null;

    /**
     * @var View
     */
    protected View $_view;

        /**
     * @var Catalog
     */
    protected Catalog $_catalog;

    /**
     * @param Context $context
     * @param Config $config
     * @param DataLayerHelper $dataLayerHelper
     * @param DataLayer $dataLayer
     * @param View $view
     * @param Catalog $catalog
     * @param array $data
     */
    public function __construct(Context $context, Config $config, DataLayerHelper $dataLayerHelper, DataLayer $dataLayer, View $view, Catalog $catalog, array $data = [])
    {
        $this->_config = $config;
        $this->_dataLayerHelper = $dataLayerHelper;
        $this->_dataLayerModel = $dataLayer;
        $this->_view = $view;
        $this->_catalog = $catalog;

        parent::__construct($context, $data);
    }



    /**
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_config->isEnabled()) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @return array
     */
    public function getDataLayer()
    {
        $this->_dataLayerModel->setPageDataLayer();
        $data = $this->_dataLayerHelper->mappifyPage($this->_dataLayerModel->getVariables());
        $data = $this->_config->removeParameterByBlacklist($data);
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private function getProductId(): ?int
    {
        $product = $this->_view->getProduct();
        $productId = $product?->getId();
        if(is_null($productId)) {
            $product = $this->_catalog->getProduct();
            return $product?->getId();
        }
        return $productId;
    }

    /**
     * @return string
     */
    public function getScript()
    {
        return TrackingScript::generateJS($this->_config->getConfig(), $this->getProductId());
    }
}
