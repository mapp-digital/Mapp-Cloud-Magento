<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Page\Config as FrameworkPageConfig;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Helper\Data as Catalog;
use MappDigital\Cloud\Helper\Config;
use MappDigital\Cloud\Helper\TrackingScript;
use MappDigital\Cloud\Helper\DataLayer as DataLayerHelper;
use MappDigital\Cloud\Model\DataLayer;

class TIDatalayer extends Template
{
    protected $pageConfig;

    public function __construct(
        protected Config $config,
        protected DataLayerHelper $dataLayerHelper,
        protected DataLayer $dataLayerModel,
        protected View $view,
        protected Catalog $catalog,
        Context $context,
        FrameworkPageConfig $pageConfig,
        array $data = []
    ){
        parent::__construct($context, $data);

        $this->pageConfig = $pageConfig;
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->config->isEnabled()) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @return string
     */
    public function getDataLayer(): string
    {
        $this->dataLayerModel->setPageDataLayer();
        $data = $this->dataLayerHelper->mappifyPage($this->dataLayerModel->getVariables());
        $data = $this->config->removeParameterByBlacklist($data);
        return json_encode($data ?? [], JSON_PRETTY_PRINT);
    }

    /**
     * @return string
     */
    public function getScript(): string
    {
        return TrackingScript::generateJS($this->config->getConfig(), $this->getProductId());
    }


    /**
     * @return int|null
     */
    private function getProductId(): ?int
    {
        $product = $this->view->getProduct();
        $productId = $product?->getId();

        if (is_null($productId)) {
            $product = $this->catalog->getProduct();
            return $product?->getId();
        }

        return $productId;
    }
}