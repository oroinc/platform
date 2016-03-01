<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LazyServicesCompilerPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    protected $lazyServicesTags = array(
        'doctrine.event_listener'
    );

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->setLazyServicesByConfig($container);

        foreach ($this->lazyServicesTags as $tagName) {
            $this->setLazyPrivateServicesByTag($container, $tagName);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function setLazyServicesByConfig(ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_lazy_services',
            new YamlCumulativeFileLoader('Resources/config/oro/lazy_services.yml')
        );

        $lazyServices = array();
        foreach ($configLoader->load($container) as $resource) {
            if (!empty($resource->data['lazy_services']) && is_array($resource->data['lazy_services'])) {
                $lazyServices = array_merge($lazyServices, $resource->data['lazy_services']);
            }
        }

        foreach ($lazyServices as $serviceId) {
            if ($container->hasDefinition($serviceId)) {
                $container->getDefinition($serviceId)->setLazy(true);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string $tagName
     */
    protected function setLazyPrivateServicesByTag(ContainerBuilder $container, $tagName)
    {
        $lazyServices = array_keys($container->findTaggedServiceIds($tagName));

        foreach ($lazyServices as $serviceId) {
            if ($container->hasDefinition($serviceId)) {
                $container->getDefinition($serviceId)->setLazy(true)->setPublic(false);
            }
        }
    }
}
