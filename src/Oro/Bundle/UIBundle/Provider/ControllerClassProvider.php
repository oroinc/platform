<?php

namespace Oro\Bundle\UIBundle\Provider;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\ResourcesContainerInterface;
use ProxyManager\Proxy\VirtualProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Resource\ReflectionClassResource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * The provider for all registered controllers.
 */
class ControllerClassProvider extends PhpArrayConfigProvider
{
    /** @var RouterInterface */
    private $router;

    /** @var ContainerInterface */
    private $container;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        string $cacheFile,
        bool $debug,
        RouterInterface $router,
        ContainerInterface $container,
        LoggerInterface $logger
    ) {
        parent::__construct($cacheFile, $debug);
        $this->router = $router;
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * Gets all registered controllers.
     *
     * @return array [route name => [controller class, action method name], ...]
     */
    public function getControllers(): array
    {
        return $this->doGetConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $config = [];
        $classes = [];
        $collection = $this->router->getRouteCollection();
        foreach ($collection as $routeName => $route) {
            $controller = null;
            try {
                $controller = $this->getController($route);
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf('Cannot extract controller for "%s" route.', $routeName),
                    ['exception' => $e]
                );
            }
            if ($controller) {
                $config[$routeName] = $controller;
                $className = $controller[0];
                if (!isset($classes[$className])) {
                    $classes[$className] = true;
                }
            }
        }
        foreach ($classes as $className => $val) {
            try {
                $reflClass = new \ReflectionClass($className);
            } catch (\ReflectionException $e) {
                $this->logger->error(sprintf('Undefined controller class "%s".', $className), ['exception' => $e]);
                continue;
            }
            $resourcesContainer->addResource(new ReflectionClassResource($reflClass));
        }

        return $config;
    }

    private function isIgnoredService(string $service): bool
    {
        return str_starts_with($service, 'web_profiler.');
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param Route $route
     *
     * @return array|null [controller class, action method name] or NULL, if the controller does not exist
     */
    private function getController(Route $route): ?array
    {
        $controller = $route->getDefault('_controller');
        if (!$controller || !\is_string($controller)) {
            return null;
        }

        [$className, $methodName] = $this->resolveController($controller);
        if (!class_exists($className)) {
            $className = $this->resolveControllerClass($className);
        }

        if (!$className) {
            return null;
        }
        if (!$methodName) {
            if (!method_exists($className, '__invoke')) {
                throw new \InvalidArgumentException(sprintf(
                    'Controller class "%s" should have "__invoke" method.',
                    $className
                ));
            }
            $methodName = '__invoke';
        }

        return [$className, $methodName];
    }

    private function resolveController(string $controller): array
    {
        // check for "class::method"
        if (str_contains($controller, '::')) {
            return explode('::', $controller);
        }

        $separatorCount = substr_count($controller, ':');

        // check for "service:method" or "service"
        $className = null;
        $methodName = null;
        if (1 === $separatorCount) {
            [$className, $methodName] = explode(':', $controller);
        } elseif (0 === $separatorCount) {
            $className = $controller;
        }

        return [$className, $methodName];
    }

    private function resolveControllerClass(string $service): ?string
    {
        if (!$this->container->has($service)) {
            throw new \InvalidArgumentException(sprintf('Undefined controller service "%s".', $service));
        }

        if ($this->isIgnoredService($service)) {
            return null;
        }

        $controller = $this->container->get($service);
        if ($controller instanceof VirtualProxyInterface) {
            if (!$controller->isProxyInitialized()) {
                $controller->initializeProxy();
            }
            $controller = $controller->getWrappedValueHolderValue();
        }

        return \get_class($controller);
    }
}
