<?php

namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\Cache;
use Oro\Bundle\NavigationBundle\Annotation\TitleTemplate;
use Symfony\Component\Routing\Router;

/**
 * Reads page titles from TitleTemplate annotations of controllers.
 */
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
     * @param Reader $reader
     * @param Router $router
     * @param Cache $cache
     */
    public function __construct(Reader $reader, Router $router, Cache $cache)
    {
        $this->reader = $reader;
        $this->router = $router;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle($route)
    {
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
        $classes = $this->cache->fetch(self::CACHE_KEY);
        if (false !== $classes) {
            return $classes;
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
