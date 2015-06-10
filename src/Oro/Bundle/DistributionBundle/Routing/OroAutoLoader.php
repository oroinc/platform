<?php

namespace Oro\Bundle\DistributionBundle\Routing;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;

use Symfony\Component\Routing\RouteCollection;

class OroAutoLoader extends AbstractLoader
{
    /**
     * {@inheritdoc}
     */
    public function load($file, $type = null)
    {
        $routes = new RouteCollection();
        $bundles = $this->kernel->getBundles();

        foreach ($bundles as $bundle) {
            try {
                $routes->addCollection(parent::load($bundle->getPath() . '/Resources/config/oro/routing.yml', $type));
            } catch (\InvalidArgumentException $e) {
                continue;
            }
        }

        return $this->dispatchEvent(RouteCollectionEvent::AUTOLOAD, $routes);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'oro_auto' === $type;
    }
}
