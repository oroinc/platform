<?php

namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\VoidCache;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;

use Oro\Bundle\NavigationBundle\Annotation\TitleTemplate;

class AnnotationsReader implements ReaderInterface
{
    const CACHE_KEY = 'controller_classes';

    /** @var Reader */
    private $reader;

    /** @var Router */
    private $router;

    /** @var Cache */
    private $cache;

    /**
     * @param RequestStack $requestStack
     * @param Reader       $reader
     */
    public function __construct(RequestStack $requestStack, Reader $reader)
    {
        $this->reader = $reader;
        $this->cache = new VoidCache();
    }

    /**
     * @param Router $router
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param Cache $cache
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle($route)
    {
        if (!$this->router) {
            return null;
        }

        $classes = $this->getControllerClasses();

        if (array_key_exists($route, $classes)) {
            $controller = $classes[$route];
            if (false === strpos($controller, '::')) {
                return null;
            }

            list($class, $method) = explode('::', $controller, 2);

            $reflectionMethod = new \ReflectionMethod($class, $method);

            $annotation = $this->reader->getMethodAnnotation($reflectionMethod, TitleTemplate::class);
            if ($annotation) {
                return $annotation->getValue();
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getControllerClasses()
    {
        if ($this->cache->contains(self::CACHE_KEY)) {
            return $this->cache->fetch(self::CACHE_KEY);
        }

        $classes = [];

        $collection = $this->router->getRouteCollection();
        if (null !== $collection) {
            foreach ($collection as $routeName => $route) {
                $classes[$routeName] = $route->getDefault('_controller');
            }
        }

        $this->cache->save(self::CACHE_KEY, $classes);

        return $classes;
    }
}
