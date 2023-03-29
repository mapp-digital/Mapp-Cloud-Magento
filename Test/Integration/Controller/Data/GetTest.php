<?php

namespace MappDigital\Cloud\Test\Integration\Controller\Data;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type\Price;
use Magento\Catalog\Model\Session as ProductCatalogSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Http;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\View\Result\Page;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Confirming TI Datalayer Expected Values.
 *
 * @magentoDataFixture MappDigital_Cloud::Test/Integration/_files/mapp_product_simple.php
 * @magentoDataFixture MappDigital_Cloud::Test/Integration/_files/mapp_category.php

 * @magentoDbIsolation enabled
 */
class GetTest extends AbstractController
{
    const REQUIRED_PAGE_DEFAULT_DATALAYER_KEYS = [
        'pageAction',
        'pageRoute',
        'pageStoreFrontendName',
        'pageStoreName',
        'pageStoreId',
        'pageLocale',
        'pageLanguage',
        'pageTitle',
        'pageContentId'
    ];

    const REQUIRED_PRODUCT_DEFAULT_DATALAYER_KEYS = [
        'productEntityId',
        'productCreatedAt',
        'productUpdatedAt',
        'productSku',
        'productPrice',
        'productTaxClassId',
        'productWeight',
        'productVisibility',
        'productIsReturnable',
        'productShortDescription',
        'productDescription',
        'productUrlKey',
        'productMetaTitle',
        'productMetaKeyword',
        'productMetaDescription',
        'productAttributeSetId',
        'productTypeId',
        'productName',
    ];

    const REQUIRED_ORDER_DEFAULT_DATALAYER_KEYS = [
        'orderId',
        'orderValue',
        'orderTotalDue',
        'orderTotalItemCount',
        'orderCurrency',
        'orderWeight',
        'orderDiscountAmount',
        'orderDiscountAmountTaxCompensation',
        'orderShippingMethod',
        'orderShippingDescription',
        'orderShippingAmount',
        'orderShippingAmountDiscount',
        'orderShippingAmountDiscountTaxCompensation',
        'orderShippingAmountInclTax',
        'orderSubtotal',
        'orderSubtotalInclTax',
        'orderTaxAmount',
        'orderPaymentAmountRefunded',
        'orderPaymentAmountCanceled',
        'orderBillingPostcode',
        'orderBillingLastname',
        'orderBillingStreet',
        'orderBillingCity',
        'orderBillingEmail',
        'orderBillingTelephone',
        'orderBillingCountryId',
        'orderBillingFirstname',
        'orderBillingAddressType',
        'orderBillingVatId',
        'orderShippingPostcode',
        'orderShippingEmail',
        'orderShippingTelephone',
        'orderShippingCountryId',
        'orderShippingFirstname',
        'orderShippingAddressType'
    ];

    const REQUIRED_CUSTOMER_DEFAULT_DATALAYER_KEYS = [
        'customerEntityId',
        'customerGroupId',
        'customerDefaultBilling',
        'customerDefaultShipping',
        'customerCreatedAt',
        'customerUpdatedAt',
        'customerEmail',
        'customerFirstname',
        'customerLastname',
        'customerMiddlename',
        'customerPrefix',
        'customerSuffix',
        'customerGender',
        'customerStoreId',
        'customerTaxvat',
        'customerWebsiteId'
    ];

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Page
     */
    private $page;

    /**
     * @var Price
     */
    private $productPrice;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var ProductCatalogSession
     */
    protected $catalogSession;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var GuestCartManagementInterface
     */
    private $guestCartManagement;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->session = $this->_objectManager->get(Session::class);
        $this->page = $this->_objectManager->get(Page::class);
        $this->productPrice = $this->_objectManager->create(Price::class);
        $this->productRepository = $this->_objectManager->create(ProductRepositoryInterface::class);
        $this->categoryRepository = $this->_objectManager->create(CategoryRepositoryInterface::class);
        $this->customerSession = $this->_objectManager->get(Session::class);
        $this->catalogSession = $this->_objectManager->get(ProductCatalogSession::class);
        $this->quote = $this->_objectManager->get(Quote::class);
        $this->checkoutSession = $this->_objectManager->get(CheckoutSession::class);
        $this->quoteIdMaskFactory = $this->_objectManager->get(QuoteIdMaskFactory::class);
        $this->guestCartManagement = $this->_objectManager->get(GuestCartManagementInterface::class);
        $this->orderRepository = $this->_objectManager->get(OrderRepository::class);
    }

    /**
     * @magentoConfigFixture current_store mapp_acquire/general/enable 1
     * @magentoConfigFixture current_store mapp_acquire/general/acquire (function(e){var t=document,n=t.createElement("script");n.async=!0,n.defer=!0,n.src=e,t.getElementsByTagName("head")[0].appendChild(n)})("https://go.flx1.com/100-20000.js?id=20000&m=100")
     * @return void
     */
    public function testAcquireRendersOnPageCorrectly()
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->dispatch('/');

        $this->assertStringContainsString('100', $this->getResponse());
        $this->assertStringContainsString('20000', $this->getResponse());
    }

    /**
     * @magentoConfigFixture current_store customer/captcha/enable 0
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @return void
     */
    public function testCookieSetAfterPageLoadWhenLoggedIn()
    {
        $this->prepareRequest('customer@example.com', 'password');
        $this->dispatch('customer/account/loginPost');
        $this->resetRequest();

        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->dispatch('/customer/account');
        $this->resetRequest();

        $dataLayer = $this->prepareAndGoToDataLayerGetEndpointAndReturnDataLayerResponse();

        $this->assertThatAllBasicPageKeysExistInDataLayerArray($dataLayer);
        $this->assertThatAllBasicCustomerKeysExistInDataLayerArray($dataLayer);
    }

    /**
     * @magentoDataFixture MappDigital_Cloud::Test/Integration/_files/mapp_guest_quote_with_addresses.php
     * @magentoConfigFixture current_store mapp_gtm/general/gtm_enable 1
     * @magentoConfigFixture current_store mapp_gtm/general/gtm_datalayer dataLayer
     * @return void
     */
    public function testOrderSuccessPageDatalayerContainsOrderDataKeys()
    {
        $this->quote->load('guest_quote_publish', 'reserved_order_id');
        /** @var ProductInterface $product */
        $this->createAndReturnOrder();
        $this->prepareAndGoToPageAndClearRequestReadyForDataTest(HttpRequest::METHOD_GET, '/checkout/onepage/success');
        $dataLayer = $this->prepareAndGoToDataLayerGetEndpointAndReturnDataLayerResponse();

        $this->assertThatAllBasicPageKeysExistInDataLayerArray($dataLayer);
        $this->assertThatAllBasicOrderKeysExistInDataLayerArray($dataLayer);
    }

    /**
     * @magentoConfigFixture current_store mapp_gtm/general/gtm_enable 1
     * @magentoConfigFixture current_store mapp_gtm/general/gtm_datalayer dataLayer
     * @return void
     */
    public function testProductPageDatalayerDoesNotContainProductDataKeysWithoutProductUrlParamAdded()
    {
        /** @var ProductInterface $product */
        $product = $this->productRepository->get('simple');
        $this->prepareAndGoToPageAndClearRequestReadyForDataTest(HttpRequest::METHOD_GET, '/catalog/product/view/id/' . $product->getId());
        $dataLayer = $this->prepareAndGoToDataLayerGetEndpointAndReturnDataLayerResponse();

        $this->assertThatAllBasicPageKeysExistInDataLayerArray($dataLayer);
        $this->assertArrayNotHasKey('productAddPrice', $dataLayer);
    }

    /**
     * @magentoConfigFixture current_store mapp_gtm/general/gtm_enable 1
     * @magentoConfigFixture current_store mapp_gtm/general/gtm_datalayer dataLayer
     * @return void
     */
    public function testProductPageDatalayerContainsProductDataKeysWithProductUrlParamAdded()
    {
        /** @var ProductInterface $product */
        $product = $this->productRepository->get('simple');
        $this->prepareAndGoToPageAndClearRequestReadyForDataTest(HttpRequest::METHOD_GET, '/catalog/product/view/id/' . $product->getId());
        $this->catalogSession->setData('last_viewedproduct_id', $product->getId());
        $dataLayer = $this->prepareAndGoToDataLayerGetEndpointAndReturnDataLayerResponse(['product' => $product->getUrlKey()]);

        $this->assertThatAllBasicPageKeysExistInDataLayerArray($dataLayer);
        $this->assertThatAllBasicProductKeysExistInDataLayerArray($dataLayer);
    }

    /**
     * @magentoConfigFixture current_store mapp_gtm/general/gtm_enable 1
     * @magentoConfigFixture current_store mapp_gtm/general/gtm_datalayer dataLayer
     * @return void
     */
    public function testCategoryPageDatalayerContainsCategoryDataKeys()
    {
        /** @var ProductInterface $product */
        $category = $this->categoryRepository->get('100');
        $this->prepareAndGoToPageAndClearRequestReadyForDataTest(HttpRequest::METHOD_GET, '/catalog/category/view/id/' . $category->getId());
        $dataLayer = $this->prepareAndGoToDataLayerGetEndpointAndReturnDataLayerResponse();

        $this->assertThatAllBasicPageKeysExistInDataLayerArray($dataLayer);
    }

    /**
     * @param string $method
     * @param string $uri
     * @return void
     */
    private function prepareAndGoToPageAndClearRequestReadyForDataTest(string $method, string $uri)
    {
        $this->getRequest()->setMethod($method);
        $this->dispatch($uri);
        $this->resetRequest();
    }

    /**
     * @param array $additionalParams
     * @return array
     */
    private function prepareAndGoToDataLayerGetEndpointAndReturnDataLayerResponse(array $additionalParams = []): array
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->getRequest()->setParams(array_merge(['isAjax' => true], $additionalParams));
        $this->dispatch('/mappintelligence/data/get');
        $response = \json_decode($this->getResponse()->getBody(), true);

        return $response['dataLayer'] ?? [];
    }

    /**
     * Ensure that datalayer has all basic keys and details
     *
     * @param array $dataLayer
     * @return void
     */
    private function assertThatAllBasicPageKeysExistInDataLayerArray(array $dataLayer)
    {
        foreach (self::REQUIRED_PAGE_DEFAULT_DATALAYER_KEYS as $keyToAssert) {
            $this->assertArrayHasKey($keyToAssert, $dataLayer);
        }
    }

    /**
     * Ensure that datalayer has all basic keys and details
     *
     * @param array $dataLayer
     * @return void
     */
    private function assertThatAllBasicProductKeysExistInDataLayerArray(array $dataLayer)
    {
        foreach (self::REQUIRED_PRODUCT_DEFAULT_DATALAYER_KEYS as $keyToAssert) {
            $this->assertArrayHasKey($keyToAssert, $dataLayer);
        }
    }

    /**
     * Ensure that datalayer has all basic keys and details
     *
     * @param array $dataLayer
     * @return void
     */
    private function assertThatAllBasicOrderKeysExistInDataLayerArray(array $dataLayer)
    {
        foreach (self::REQUIRED_ORDER_DEFAULT_DATALAYER_KEYS as $keyToAssert) {
            $this->assertArrayHasKey($keyToAssert, $dataLayer);
        }
    }

    /**
     * Ensure that datalayer has all basic keys and details
     *
     * @param array $dataLayer
     * @return void
     */
    private function assertThatAllBasicCustomerKeysExistInDataLayerArray(array $dataLayer)
    {
        foreach (self::REQUIRED_CUSTOMER_DEFAULT_DATALAYER_KEYS as $keyToAssert) {
            $this->assertArrayHasKey($keyToAssert, $dataLayer);
        }
    }

    /**
     * Clears request and removes shared instances to allow for data request to be made.
     *
     * @return void
     */
    protected function resetRequest(): void
    {
        parent::resetRequest();
        $this->_objectManager->removeSharedInstance(Http::class);
        $this->_objectManager->removeSharedInstance(Request::class);
    }

    /**
     * @return OrderInterface
     */
    private function createAndReturnOrder()
    {
        $this->checkoutSession->setQuoteId($this->quote->getId());
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $quoteIdMask->load($this->quote->getId(), 'quote_id');
        $cartId = $quoteIdMask->getMaskedId();
        $orderId = $this->guestCartManagement->placeOrder($cartId);
        return $this->orderRepository->get($orderId);
    }

    /**
     * Prepare request
     *
     * @param string|null $email
     * @param string|null $password
     * @return void
     */
    private function prepareRequest(?string $email, ?string $password): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue([
            'login' => [
                'username' => $email,
                'password' => $password,
            ],
        ]);
    }
}



















