<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ConfigurationPass implements CompilerPassInterface
{
    const LAYOUT_FACTORY_BUILDER_SERVICE = 'oro_layout.layout_factory_builder';
    const PHP_RENDERER_SERVICE = 'oro_layout.php.layout_renderer';
    const TWIG_RENDERER_SERVICE = 'oro_layout.twig.layout_renderer';
    const LAYOUT_EXTENSION_SERVICE = 'oro_layout.extension';
    const BLOCK_TYPE_TAG_NAME = 'layout.block_type';
    const BLOCK_TYPE_EXTENSION_TAG_NAME = 'layout.block_type_extension';
    const LAYOUT_UPDATE_TAG_NAME = 'layout.layout_update';
    const CONTEXT_CONFIGURATOR_TAG_NAME = 'layout.context_configurator';
    const DATA_PROVIDER_TAG_NAME = 'layout.data_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // register renderers
        if ($container->hasDefinition(self::LAYOUT_FACTORY_BUILDER_SERVICE)) {
            $factoryBuilderDef = $container->getDefinition(self::LAYOUT_FACTORY_BUILDER_SERVICE);
            if ($container->hasDefinition(self::PHP_RENDERER_SERVICE)) {
                $factoryBuilderDef->addMethodCall(
                    'addRenderer',
                    ['php', new Reference(self::PHP_RENDERER_SERVICE)]
                );
            }
            if ($container->hasDefinition(self::TWIG_RENDERER_SERVICE)) {
                $factoryBuilderDef->addMethodCall(
                    'addRenderer',
                    ['twig', new Reference(self::TWIG_RENDERER_SERVICE)]
                );
            }
        }
        // register block types, block type extensions and layout updates
        if ($container->hasDefinition(self::LAYOUT_EXTENSION_SERVICE)) {
            $extensionDef = $container->getDefinition(self::LAYOUT_EXTENSION_SERVICE);
            $extensionDef->replaceArgument(1, $this->getBlockTypes($container));
            $extensionDef->replaceArgument(2, $this->getBlockTypeExtensions($container));
            $extensionDef->replaceArgument(3, $this->getLayoutUpdates($container));
            $extensionDef->replaceArgument(4, $this->getContextConfigurators($container));
            $extensionDef->replaceArgument(5, $this->getDataProviders($container));
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    protected function getBlockTypes(ContainerBuilder $container)
    {
        $types = [];
        foreach ($container->findTaggedServiceIds(self::BLOCK_TYPE_TAG_NAME) as $serviceId => $tag) {
            if (empty($tag[0]['alias'])) {
                throw new InvalidConfigurationException(
                    sprintf('Tag attribute "alias" is required for "%s" service.', $serviceId)
                );
            }

            $alias         = $tag[0]['alias'];
            $types[$alias] = $serviceId;
        }

        return $types;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    protected function getBlockTypeExtensions(ContainerBuilder $container)
    {
        $typeExtensions = [];
        foreach ($container->findTaggedServiceIds(self::BLOCK_TYPE_EXTENSION_TAG_NAME) as $serviceId => $tag) {
            if (empty($tag[0]['alias'])) {
                throw new InvalidConfigurationException(
                    sprintf('Tag attribute "alias" is required for "%s" service.', $serviceId)
                );
            }

            $alias    = $tag[0]['alias'];
            $priority = isset($tag[0]['priority']) ? $tag[0]['priority'] : 0;

            $typeExtensions[$alias][$priority][] = $serviceId;
        }
        foreach ($typeExtensions as $key => $items) {
            ksort($items);
            $typeExtensions[$key] = call_user_func_array('array_merge', $items);
        }

        return $typeExtensions;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    protected function getLayoutUpdates(ContainerBuilder $container)
    {
        $layoutUpdates = [];
        foreach ($container->findTaggedServiceIds(self::LAYOUT_UPDATE_TAG_NAME) as $serviceId => $tag) {
            if (empty($tag[0]['id'])) {
                throw new InvalidConfigurationException(
                    sprintf('Tag attribute "id" is required for "%s" service.', $serviceId)
                );
            }

            $id       = $tag[0]['id'];
            $priority = isset($tag[0]['priority']) ? $tag[0]['priority'] : 0;

            $layoutUpdates[$id][$priority][] = $serviceId;
        }
        foreach ($layoutUpdates as $key => $items) {
            ksort($items);
            $layoutUpdates[$key] = call_user_func_array('array_merge', $items);
        }

        return $layoutUpdates;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    protected function getContextConfigurators(ContainerBuilder $container)
    {
        $configurators = [];
        foreach ($container->findTaggedServiceIds(self::CONTEXT_CONFIGURATOR_TAG_NAME) as $serviceId => $tag) {
            $priority = isset($tag[0]['priority']) ? $tag[0]['priority'] : 0;

            $configurators[$priority][] = $serviceId;
        }
        if (!empty($configurators)) {
            ksort($configurators);
            $configurators = call_user_func_array('array_merge', $configurators);
        }

        return $configurators;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    protected function getDataProviders(ContainerBuilder $container)
    {
        $dataProviders = [];
        foreach ($container->findTaggedServiceIds(self::DATA_PROVIDER_TAG_NAME) as $serviceId => $tag) {
            if (empty($tag[0]['alias'])) {
                throw new InvalidConfigurationException(
                    sprintf('Tag attribute "alias" is required for "%s" service.', $serviceId)
                );
            }

            $alias                 = $tag[0]['alias'];
            $dataProviders[$alias] = $serviceId;
        }

        return $dataProviders;
    }
}
