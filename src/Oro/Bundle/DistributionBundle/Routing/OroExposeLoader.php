<?php

namespace Oro\Bundle\DistributionBundle\Routing;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads routes from Oro bundle routing configuration and filters them to only expose routes marked as exposable.
 */
class OroExposeLoader extends AbstractLoader
{
    public function __construct(
        KernelInterface $kernel,
        RouteOptionsResolverInterface $routeOptionsResolver
    ) {
        parent::__construct(
            $kernel,
            $routeOptionsResolver,
            ['Resources/config/oro/routing.yml'],
            'oro_expose'
        );
    }

    #[\Override]
    public function load($file, $type = null): mixed
    {
        $routes = parent::load($file, $type);

        return $this->dispatchEvent(RouteCollectionEvent::EXPOSE, $routes);
    }

    #[\Override]
    protected function loadRoutes(RouteCollection $routes)
    {
        parent::loadRoutes($routes);

        $toRemove = [];
        /** @var Route $route */
        foreach ($routes->all() as $name => $route) {
            if (!$route->getOption('expose')) {
                $toRemove[] = $name;
            }
        }
        $routes->remove($toRemove);
    }
}
