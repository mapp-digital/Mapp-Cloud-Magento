<?php
namespace MappDigital\Cloud\Test\Integration\Block;

use Magento\Framework\App\Http;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController;

class FirebaseDataTest extends AbstractController
{
    /**
     * @magentoConfigFixture current_store mapp_web_push/general/enable 1
     * @magentoConfigFixture current_store mapp_web_push/firebase/firebase_version test_firebase_version
     * @magentoConfigFixture current_store mapp_web_push/firebase/api_key test_api_key
     * @magentoConfigFixture current_store mapp_web_push/firebase/auth_domain test_auth_domain
     * @magentoConfigFixture current_store mapp_web_push/firebase/project_id test_project_id
     * @magentoConfigFixture current_store mapp_web_push/firebase/storage_bucket test_storage_bucket
     * @magentoConfigFixture current_store mapp_web_push/firebase/message_sender_id test_message_sender_id
     * @magentoConfigFixture current_store mapp_web_push/firebase/app_id test_message_app_id
     * @magentoConfigFixture current_store mapp_web_push/firebase/measurement_id test_measurement_id
     *
     * @return void
     */
    public function testFirebaseDataControllerReturnsPopulatedJavascript()
    {
        $this->getRequest(HttpRequest::METHOD_GET);
        $this->dispatch('/firebase-messaging-sw.js');

        foreach (
            [
                'test_firebase_version',
                'test_api_key',
                'test_auth_domain',
                'test_project_id',
                'test_storage_bucket',
                'test_message_sender_id',
                'test_message_app_id',
                'test_measurement_id'
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
