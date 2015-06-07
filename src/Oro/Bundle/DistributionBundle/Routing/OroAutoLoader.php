<?php

namespace Oro\Bundle\DistributionBundle\Routing;

use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class OroAutoLoader extends YamlFileLoader
{
    /** @var HttpKernelInterface */
    protected $kernel;

    /** @var RouteOptionsResolverInterface */
    protected $routeOptionsResolver;

    /**
     * @param FileLocatorInterface          $locator
     * @param HttpKernelInterface           $kernel
     * @param RouteOptionsResolverInterface $routeOptionsResolver
     */
    public function __construct(
        FileLocatorInterface $locator,
        HttpKernelInterface $kernel,
        RouteOptionsResolverInterface $routeOptionsResolver
    ) {
        parent::__construct($locator);

        $this->kernel               = $kernel;
        $this->routeOptionsResolver = $routeOptionsResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function load($file, $type = null)
    {
        $routes = new RouteCollection();

        foreach ($this->kernel->getBundles() as $bundle) {
            $path = $bundle->getPath() . '/Resources/config/oro/routing.yml';

            if (is_file($path)) {
                $routes->addCollection(parent::load($path, $type));
            }
        }

        /** @var Route $route */
        foreach ($routes as $route) {
            $this->routeOptionsResolver->resolve($route);
        }

        return $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'oro_auto' === $type;
    }
}
