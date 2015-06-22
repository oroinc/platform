<?php

namespace Oro\Bundle\DistributionBundle\Routing;

use Symfony\Component\HttpKernel\KernelInterface;

use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;

class OroAutoLoader extends AbstractLoader
{
    /**
     * @param KernelInterface               $kernel
     * @param RouteOptionsResolverInterface $routeOptionsResolver
     */
    public function __construct(
        KernelInterface $kernel,
        RouteOptionsResolverInterface $routeOptionsResolver
    ) {
        parent::__construct(
            $kernel,
            $routeOptionsResolver,
            ['Resources/config/oro/routing.yml'],
            'oro_auto'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function load($file, $type = null)
    {
        $routes = parent::load($file, $type);

        return $this->dispatchEvent(RouteCollectionEvent::AUTOLOAD, $routes);
    }
}
