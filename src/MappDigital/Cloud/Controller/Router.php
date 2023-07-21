<?php
namespace MappDigital\Cloud\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Framework\App\RouterInterface;
use ReflectionException;

/**
 * Matches application action in case when pixel-webpush.min.js or firebase-messaging-sw.js file was requested
 */
class Router implements RouterInterface
{
    private ActionFactory $actionFactory;
    private ActionList $actionList;
    private ConfigInterface $routeConfig;

    public function __construct(
        ActionFactory $actionFactory,
        ActionList $actionList,
        ConfigInterface $routeConfig
    ) {
        $this->actionFactory = $actionFactory;
        $this->actionList = $actionList;
        $this->routeConfig = $routeConfig;
    }

    /**
     * Checks if Web Push Js files were requested and returns instance of matched application action class
     *
     * @param RequestInterface $request
     * @return ActionInterface|null
     * @throws ReflectionException
     */
    public function match(RequestInterface $request)
    {
        $identifier = trim($request->getPathInfo(), '/');

        if ($identifier !== 'pixel-webpush.min.js' && $identifier !== 'firebase-messaging-sw.js') {
            return null;
        }

        $modules = $this->routeConfig->getModulesByFrontName('mappintelligence');
        if (empty($modules)) {
            return null;
        }

        if ($identifier === 'pixel-webpush.min.js') {
            $actionClassName = $this->actionList->get($modules[0], null, 'index', 'pixel');
        } else {
            $actionClassName = $this->actionList->get($modules[0], null, 'index', 'firebase');
        }

        return $this->actionFactory->create($actionClassName);
    }
}
