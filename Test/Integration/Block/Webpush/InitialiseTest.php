<?php

namespace MappDigital\Cloud\Test\Integration\Block\Webpush;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Http;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\View\Result\Page;
use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController;
use MappDigital\Cloud\Plugin\AddWebpushFirebaseLayoutHandlePlugin;

class InitialiseTest extends AbstractController
{
    private ?Session $session;
    private ?Page $page;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->session = $this->_objectManager->get(Session::class);
        $this->page = $this->_objectManager->get(Page::class);
    }

    /**
     * Confirm that no alias can be found on the homepage when not logged in
     *
     * @return void
     */
    public function testThatNoUserAliasIsReturnedWhenLoggedOutAndNotOnOrderSuccessPage()
    {
        $this->goToHomePage();
        $this->assertStringNotContainsString('pixel-webpush.min.js', $this->getResponse()->getBody());
        $this->assertStringNotContainsString('firebase-messaging-sw.js', $this->getResponse()->getBody());
        $this->assertStringNotContainsString('window.mappWebpushMessage', $this->getResponse()->getBody());
    }

    /**
     * Confirm that alias can be found on the homepage when logged in
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @return void
     */
    public function testThatUserAliasIsReturnedWhenLoggedIn()
    {
        $this->loginAsCustomerAndAssertTheyAreLoggedInAndResetRequest();
        $this->goToHomePage();
        $this->assertStringContainsString('pixel-webpush.min.js', $this->getResponse()->getBody());
        $this->assertStringContainsString('firebase-messaging-sw.js', $this->getResponse()->getBody());
        $this->assertStringContainsString('window.mappWebpushMessage', $this->getResponse()->getBody());
    }

    /**
     * @return void
     */
    public function testLayoutHandleAddedIfCookieNotSetAfterPageLoad()
    {
        $this->goToHomePage();
        $handles = $this->page->getLayout()->getUpdate()->getHandles();
        $this->assertContains(AddWebpushFirebaseLayoutHandlePlugin::WEBPUSH_JS_HANDLE, $handles);
    }

    /**
     * @return void
     */
    public function testCookieNotSetAfterPageLoad()
    {
        $this->goToHomePage();

        $cookie = $this->getRequest()->getCookie(AddWebpushFirebaseLayoutHandlePlugin::COOKIE_NAME_WEBPUSH_SET);
        $this->assertNull($cookie);
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
        $this->assertTrue($this->session->isLoggedIn());

        $this->resetRequest();
        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->dispatch('/');

        $cookie = $this->getRequest()->getCookie(AddWebpushFirebaseLayoutHandlePlugin::COOKIE_NAME_WEBPUSH_SET);
        $this->assertNotNull($cookie);
    }

    private function loginAsCustomerAndAssertTheyAreLoggedInAndResetRequest()
    {
        $this->prepareRequest('customer@example.com', 'password');
        $this->dispatch('customer/account/loginPost');
        $this->assertTrue($this->session->isLoggedIn());

        $this->resetRequest();
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

    private function goToHomePage()
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->dispatch('/');
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
}
