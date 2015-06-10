<?php

namespace Oro\Bundle\DistributionBundle\Routing;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class OroExposeLoader extends AbstractLoader
{
    const OPTION_EXPOSE = 'expose';

    /**
     * {@inheritdoc}
     */
    public function load($file, $type = null)
    {
        $routes = new RouteCollection();

        foreach ($this->kernel->getBundles() as $bundle) {
            try {
                $collection = parent::load($bundle->getPath() . '/Resources/config/oro/routing.yml', $type);

                /** @var Route $route */
                foreach ($collection->getIterator() as $routeName => $route) {
                    if ($route->hasOption(self::OPTION_EXPOSE) && $route->getOption(self::OPTION_EXPOSE)) {
                        $routes->add($routeName, $route);
                    }
                }
            } catch (\InvalidArgumentException $e) {
                continue;
            }
        }

        return $this->dispatchEvent(RouteCollectionEvent::EXPOSE, $routes);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'oro_expose' === $type;
    }
}
