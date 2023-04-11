<?php
namespace MappDigital\Cloud\Test\Integration\Block;

use Magento\Framework\App\Http;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController;

class PixelDataTest extends AbstractController
{
    /**
     * @magentoConfigFixture current_store mapp_web_push/pixel/service_url test_service_url
     * @magentoConfigFixture current_store mapp_web_push/pixel/webpush_script_included test_webpush_script_included
     * @magentoConfigFixture current_store mapp_web_push/pixel/use_user_matching test_use_user_matching
     * @magentoConfigFixture current_store mapp_web_push/pixel/x_key test_x_key
     * @magentoConfigFixture current_store mapp_web_push/pixel/service_worker_script test_service_worker_script
     * @magentoConfigFixture current_store mapp_web_push/pixel/include_firebase_scripts test_include_firebase_scripts
     *
     * @return void
     */
    public function testPixelDataControllerReturnsPopulatedJavascript()
    {
        $this->getRequest(HttpRequest::METHOD_GET);
        $this->dispatch('/pixel-webpush.min.js');

        foreach (
            [
                'test_service_url',
                'test_webpush_script_included',
                'test_use_user_matching',
                'test_x_key',
                'test_service_worker_script',
                'test_include_firebase_scripts'
            ] as $configValue) {
            $this->assertStringContainsString($configValue, $this->getResponse());
        }

        $this->assertStringNotContainsString("<html>", $this->getResponse());
        $this->assertStringNotContainsString("<body>", $this->getResponse());
        $this->assertStringNotContainsString("<script>", $this->getResponse());
        $this->assertStringNotContainsString("<media>", $this->getResponse());
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
