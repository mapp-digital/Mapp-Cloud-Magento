<?php
declare(strict_types=1);

namespace MappDigital\Cloud\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Processes request to firebase-messaging-sw.js file and returns firebase-messaging-sw.js content as result
 */
class Firebase extends Action implements HttpGetActionInterface
{
    private PageFactory $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Generates firebase-messaging-sw.js data and returns it as result
     *
     * @return Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create(true);
        $resultPage->addHandle('mappintelligence_index_firebase');
        $resultPage->setHeader('Content-Type', 'text/plain');

        return $resultPage;
    }
}
