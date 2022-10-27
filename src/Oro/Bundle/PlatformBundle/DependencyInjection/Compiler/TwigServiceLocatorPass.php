<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\ExtensionInterface;

/**
 * Registers all services inside service locator which are required by twig extensions.
 */
class TwigServiceLocatorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oro_platform.twig.service_locator')) {
            return;
        }

        $ids = [[]];
        foreach ($container->getDefinitions() as $definition) {
            $this->getSubscribedServices($container, $definition, $ids);
        }

        $services = [];
        foreach (array_merge(...$ids) as $alias => $id) {
            if (\is_string($alias)) {
                $id = $alias;
            }

            if (!isset($services[$id])) {
                $services[$id] = new Reference($id, ContainerInterface::IGNORE_ON_INVALID_REFERENCE);
            }
        }

        if (!$services) {
            return;
        }

        $container->getDefinition('oro_platform.twig.service_locator')
            ->replaceArgument(0, $services);
    }

    private function getSubscribedServices(ContainerBuilder $container, Definition $definition, array &$ids): void
    {
        $class = $definition->getClass();
        if (!is_a($class, ExtensionInterface::class, true)) {
            return;
        }

        if (is_a($class, ServiceSubscriberInterface::class, true)) {
            $ids[] = $class::getSubscribedServices();
        }

        [$decoratedService] = $definition->getDecoratedService();
        if (!$decoratedService) {
            return;
        }

        $this->getSubscribedServices($container, $container->getDefinition($decoratedService), $ids);
    }
}
