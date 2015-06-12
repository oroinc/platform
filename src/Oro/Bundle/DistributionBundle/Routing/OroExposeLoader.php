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
                $path = $this->locator->locate('Resources/config/oro/routing.yml', $bundle->getPath());
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $collection = parent::load($path, $type);

            /** @var Route $route */
            foreach ($collection->getIterator() as $routeName => $route) {
                if ($route->hasOption(self::OPTION_EXPOSE) && $route->getOption(self::OPTION_EXPOSE)) {
                    $routes->add($routeName, $route);
                }
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
