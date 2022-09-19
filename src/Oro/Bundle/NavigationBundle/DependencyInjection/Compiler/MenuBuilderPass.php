<?php

namespace Oro\Bundle\NavigationBundle\DependencyInjection\Compiler;

use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all menu and navigation item builders.
 */
class MenuBuilderPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $this->processMenu($container);
        $this->processItems($container);
    }

    private function processMenu(ContainerBuilder $container): void
    {
        $builders = [];
        $services = [];
        $items = $this->findAndInverseSortTaggedServices(
            'oro_menu.builder',
            $container,
            BuilderChainProvider::COMMON_BUILDER_ALIAS
        );
        foreach ($items as [$alias, $id]) {
            $builders[$alias][] = $id;
            if (!isset($services[$alias])) {
                $services[$id] = new Reference($id);
            }
        }

        $container->getDefinition('oro_menu.builder_chain')
            ->setArgument('$builders', $builders)
            ->setArgument('$builderContainer', ServiceLocatorTagPass::register($container, $services));
    }

    private function processItems(ContainerBuilder $container): void
    {
        $services = [];
        $items = $this->findAndInverseSortTaggedServices('oro_navigation.item.builder', $container);
        foreach ($items as [$alias, $id]) {
            if (!isset($services[$alias])) {
                $container->getDefinition($id)->addArgument($alias);
                $services[$alias] = new Reference($id);
            }
        }

        $container->getDefinition('oro_navigation.item.factory')
            ->setArgument('$builders', ServiceLocatorTagPass::register($container, $services));
    }

    private function findAndInverseSortTaggedServices(
        string $tagName,
        ContainerBuilder $container,
        string $defaultAlias = null
    ): array {
        $items = [];
        $taggedServices = $container->findTaggedServiceIds($tagName);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $items[$this->getPriorityAttribute($attributes)][] = [
                    $this->getAlias($attributes, $id, $tagName, $defaultAlias),
                    $id
                ];
            }
        }
        if ($items) {
            ksort($items);
            $items = array_merge(...array_values($items));
        }

        return $items;
    }

    private function getAlias(array $attributes, string $id, string $tagName, ?string $defaultAlias): string
    {
        if (!$defaultAlias) {
            return $this->getRequiredAttribute($attributes, 'alias', $id, $tagName);
        }

        return $this->getAttribute($attributes, 'alias') ?: $defaultAlias;
    }
}
