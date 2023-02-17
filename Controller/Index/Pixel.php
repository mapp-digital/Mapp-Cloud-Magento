<?php
declare(strict_types=1);

namespace MappDigital\Cloud\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Processes request to pixel-webpush.min.js file and returns pixel-webpush.min.js content as result
 */
class Pixel extends Action implements HttpGetActionInterface
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
     * Generates pixel-webpush.min.js data and returns it as result
     *
     * @return Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create(true);
        $resultPage->addHandle('mappintelligence_index_pixel');
        $resultPage->setHeader('Content-Type', 'application/javascript');

        return $resultPage;
    }
}
