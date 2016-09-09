<?php

namespace Oro\Bundle\DistributionBundle\Routing;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;

class OroExposeLoader extends AbstractLoader
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
            'oro_expose'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function load($file, $type = null)
    {
        $routes = parent::load($file, $type);

        return $this->dispatchEvent(RouteCollectionEvent::EXPOSE, $routes);
    }

    /**
     * {@inheritdoc}
     */
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
