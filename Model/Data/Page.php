<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2021 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Data;

use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\CatalogSearch\Helper\Data as CatalogSearchHelper;
use Magento\CatalogSearch\Model\Advanced;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Page\Title;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Pager;

class Page extends AbstractData
{
    protected Context $context;
    protected StoreManagerInterface $storeManager;
    protected CatalogHelper $catalogData;
    protected Resolver $resolver;
    protected Title $title;
    protected CatalogSearchHelper $normalSearch;
    protected Advanced $advancedSearch;
    protected Pager $pager;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CatalogHelper $catalogData,
        Resolver $resolver,
        Title $title,
        CatalogSearchHelper $normalSearch,
        Advanced $advancedSearch,
        Pager $pager
    )
    {
        $this->context = $context;
        $this->storeManager = $storeManager;
        $this->catalogData = $catalogData;
        $this->resolver = $resolver;
        $this->title = $title;
        $this->normalSearch = $normalSearch;
        $this->advancedSearch = $advancedSearch;
        $this->pager = $pager;
    }

    private function generate()
    {
        $this->setBasic();
        $this->setStore();
        $this->setLanguage();
        $this->setPageTitle();
        $this->setCategory();
        $this->setSearch();
    }

    // -----------------------------------------------
    // SETTERS AND GETTERS
    // -----------------------------------------------

    private function setStore()
    {
        $store = $this->storeManager->getStore();
        $this->set('storeFrontendName', $store->getFrontendName());
        $this->set('storeName', $store->getName());
        $this->set('storeId', $store->getId());
    }

    private function setBasic()
    {
        $request = $this->context->getRequest();
        if ($request) {
            $action = $request->getFullActionName();
            if($action === 'catalog_category_view' || $action === 'catalogsearch_result_index') {
                $this->set('number', $this->pager->getCurrentPage());
            }
            $this->set('action', $action);
            $this->set('route', $request->getRouteName());
        }
    }

    private function setLanguage()
    {
        $locale = $this->resolver->getLocale();
        if ($locale) {
            $this->set('locale', $locale);
            $this->set('language', explode('_', $locale)[0]);
        }
    }

    private function setPageTitle()
    {
        $this->set('title', $this->title->getShort());
    }

    private function setNormalSearch()
    {
        $searchTerm = $this->normalSearch->getEscapedQueryText();
        if ($searchTerm) {
            $this->set('searchType', 'normal');
            $this->set('searchTerm', htmlspecialchars_decode($searchTerm));
        }
    }

    private function setAdvancedSearch()
    {
        $advancedSearchCriterias = $this->advancedSearch->getSearchCriterias();
        if ($advancedSearchCriterias) {
            $advancedSearchTerm = [];
            foreach ($advancedSearchCriterias as $advancedSearchCriteria) {
                $advancedSearchTerm[] = $advancedSearchCriteria['value'];
            }

            $this->set('searchType', 'advanced');
            $this->set('searchTerm', implode('.', $advancedSearchTerm));
            $this->set('searchResults', $this->advancedSearch->getProductCollection()->getSize());
        }
    }

    private function setCategory()
    {
        $pageAction = $this->get('action');
        if ($pageAction) {
            $pageActions = preg_split('/[_-]/', $pageAction);
            $counter = 1;
            $categories = [
                $this->get('language')
            ];

            for ($i = 0; $i < count($pageActions); $i++) {
                if ($pageActions[$i] && $pageActions[$i] !== 'index') {
                    $this->set('category' . $counter, ucfirst($pageActions[$i]));
                    $categories[] = ucfirst($pageActions[$i]);
                    $counter++;
                }
            }

            $categories[] = $this->get('title');
            $this->set('contentId', implode('.', $categories));
        }
    }

    private function setSearch()
    {
        $this->setNormalSearch();
        $this->setAdvancedSearch();
    }

    /**
     * @return array
     */
    public function getDataLayer()
    {
        $this->generate();
        return $this->_data;
    }
}
