<?php
namespace Oro\Bundle\NavigationBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects menu builders for menu builder chain.
 */
class MenuBuilderChainPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const MENU_BUILDER_TAG = 'oro_menu.builder';
    const MENU_PROVIDER_KEY = 'oro_menu.builder_chain';
    const ITEMS_BUILDER_TAG = 'oro_navigation.item.builder';
    const ITEMS_PROVIDER_KEY = 'oro_navigation.item.factory';
    const MENU_HELPER_SERVICE = 'knp_menu.helper';

    public function process(ContainerBuilder $container)
    {
        $container->getDefinition(self::MENU_HELPER_SERVICE)->setPublic(true);
        $this->processMenu($container);
        $this->processItems($container);
    }

    protected function processMenu(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::MENU_PROVIDER_KEY)) {
            return;
        }

        $definition = $container->getDefinition(self::MENU_PROVIDER_KEY);
        $taggedServices = $this->findAndInverseSortTaggedServices(self::MENU_BUILDER_TAG, $container);

        foreach ($taggedServices as $reference) {
            $builderDefinition = $container->getDefinition((string)$reference);
            $tagAttributes = $builderDefinition->getTag(self::MENU_BUILDER_TAG);
            foreach ($tagAttributes as $attributes) {
                $addBuilderArgs = [$reference];

                if (!empty($attributes['alias'])) {
                    $addBuilderArgs[] = $attributes['alias'];
                }

                $definition->addMethodCall('addBuilder', $addBuilderArgs);
            }
        }
    }

    protected function processItems(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::ITEMS_PROVIDER_KEY)) {
            return;
        }

        $definition = $container->getDefinition(self::ITEMS_PROVIDER_KEY);
        $taggedServices = $this->findAndInverseSortTaggedServices(self::ITEMS_BUILDER_TAG, $container);

        foreach ($taggedServices as $reference) {
            $factoryDefinition = $container->getDefinition((string) $reference);
            $tagAttributes = $factoryDefinition->getTag(self::ITEMS_BUILDER_TAG);
            foreach ($tagAttributes as $attributes) {
                if (empty($attributes['alias'])) {
                    continue;
                }

                $factoryDefinition->addArgument($attributes['alias']);

                $definition->addMethodCall('addBuilder', [$reference]);
            }
        }
    }
}
