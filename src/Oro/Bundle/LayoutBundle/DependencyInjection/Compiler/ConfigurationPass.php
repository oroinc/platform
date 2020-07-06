<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Oro\Bundle\LayoutBundle\Command\DebugCommand;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers twig and php renderers for layouts, registers all block types, block type extensions, layout updates,
 * context configurators and data providers in a layout extensions and console command.
 */
class ConfigurationPass implements CompilerPassInterface
{
    private const LAYOUT_FACTORY_BUILDER_SERVICE = 'oro_layout.layout_factory_builder';
    private const PHP_RENDERER_SERVICE = 'oro_layout.php.layout_renderer';
    private const TWIG_RENDERER_SERVICE = 'oro_layout.twig.layout_renderer';
    private const LAYOUT_EXTENSION_SERVICE = 'oro_layout.extension';
    private const BLOCK_TYPE_TAG_NAME = 'layout.block_type';
    private const BLOCK_TYPE_EXTENSION_TAG_NAME = 'layout.block_type_extension';
    private const LAYOUT_UPDATE_TAG_NAME = 'layout.layout_update';
    private const CONTEXT_CONFIGURATOR_TAG_NAME = 'layout.context_configurator';
    private const DATA_PROVIDER_TAG_NAME = 'layout.data_provider';
    private const THEME_CONFIG_SERVICE = 'oro_layout.theme_extension.configuration';
    private const THEME_CONFIG_EXTENSION_TAG_NAME = 'layout.theme_config_extension';

    /** @var array */
    private $servicesForServiceLocator = [];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerRenderers($container);
        $this->registerThemeConfigExtensions($container);
        $this->configureLayoutExtension($container);

        // Adds services stored in servicesForServiceLocator to serviceLocator
        // which intend to be used instead of the container
        $container->getDefinition('oro_layout.layout.service_locator')
            ->replaceArgument(0, $this->servicesForServiceLocator);

        $this->servicesForServiceLocator = [];
    }

    /**
     * @param ContainerBuilder $container
     */
    private function registerRenderers(ContainerBuilder $container)
    {
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

    /**
     * @param ContainerBuilder $container
     */
    private function registerThemeConfigExtensions(ContainerBuilder $container)
    {
        $themeConfigurationDef = $container->getDefinition(self::THEME_CONFIG_SERVICE);
        foreach ($container->findTaggedServiceIds(self::THEME_CONFIG_EXTENSION_TAG_NAME) as $id => $attributes) {
            $themeConfigurationDef->addMethodCall('addExtension', [new Reference($id)]);
        }
    }

    /**
     * Registers block types, block type extensions and layout updates
     *
     * @param ContainerBuilder $container
     */
    private function configureLayoutExtension(ContainerBuilder $container)
    {
        $blockTypes = $this->getBlockTypes($container);
        $dataProviders = $this->getDataProviders($container);

        $extensionDef = $container->getDefinition(self::LAYOUT_EXTENSION_SERVICE);
        $extensionDef->replaceArgument(1, $blockTypes);
        $extensionDef->replaceArgument(2, $this->getBlockTypeExtensions($container));
        $extensionDef->replaceArgument(3, $this->getLayoutUpdates($container));
        $extensionDef->replaceArgument(4, $this->getContextConfigurators($container));
        $extensionDef->replaceArgument(5, $dataProviders);

        $commandDef = $container->getDefinition(DebugCommand::class);
        $commandDef->replaceArgument(2, array_keys($blockTypes));
        $commandDef->replaceArgument(3, array_keys($dataProviders));
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getBlockTypes(ContainerBuilder $container)
    {
        $types = [];
        foreach ($container->findTaggedServiceIds(self::BLOCK_TYPE_TAG_NAME) as $serviceId => $tag) {
            if (empty($tag[0]['alias'])) {
                throw new InvalidConfigurationException(
                    sprintf('Tag attribute "alias" is required for "%s" service.', $serviceId)
                );
            }

            $alias = $tag[0]['alias'];
            $types[$alias] = $serviceId;

            $this->addServiceToServiceLocator($serviceId);
        }

        return $types;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getBlockTypeExtensions(ContainerBuilder $container)
    {
        $typeExtensions = [];
        foreach ($container->findTaggedServiceIds(self::BLOCK_TYPE_EXTENSION_TAG_NAME) as $serviceId => $tag) {
            if (empty($tag[0]['alias'])) {
                throw new InvalidConfigurationException(
                    sprintf('Tag attribute "alias" is required for "%s" service.', $serviceId)
                );
            }

            $alias = $tag[0]['alias'];
            $priority = $tag[0]['priority'] ?? 0;

            $typeExtensions[$alias][$priority][] = $serviceId;

            $this->addServiceToServiceLocator($serviceId);
        }
        foreach ($typeExtensions as $key => $items) {
            ksort($items);
            $typeExtensions[$key] = array_merge(...$items);
        }

        return $typeExtensions;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getLayoutUpdates(ContainerBuilder $container)
    {
        $layoutUpdates = [];
        foreach ($container->findTaggedServiceIds(self::LAYOUT_UPDATE_TAG_NAME) as $serviceId => $tag) {
            if (empty($tag[0]['id'])) {
                throw new InvalidConfigurationException(
                    sprintf('Tag attribute "id" is required for "%s" service.', $serviceId)
                );
            }

            $id = $tag[0]['id'];
            $priority = $tag[0]['priority'] ?? 0;

            $layoutUpdates[$id][$priority][] = $serviceId;

            $this->addServiceToServiceLocator($serviceId);
        }
        foreach ($layoutUpdates as $key => $items) {
            ksort($items);
            $layoutUpdates[$key] = array_merge(...$items);
        }

        return $layoutUpdates;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getContextConfigurators(ContainerBuilder $container)
    {
        $configurators = [];
        foreach ($container->findTaggedServiceIds(self::CONTEXT_CONFIGURATOR_TAG_NAME) as $serviceId => $tag) {
            $priority = $tag[0]['priority'] ?? 0;

            $configurators[$priority][] = $serviceId;

            $this->addServiceToServiceLocator($serviceId);
        }
        if (!empty($configurators)) {
            ksort($configurators);
            $configurators = array_merge(...$configurators);
        }

        return $configurators;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getDataProviders(ContainerBuilder $container)
    {
        $dataProviders = [];
        foreach ($container->findTaggedServiceIds(self::DATA_PROVIDER_TAG_NAME) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if (empty($tag['alias'])) {
                    throw new InvalidConfigurationException(
                        sprintf('Tag attribute "alias" is required for "%s" service.', $serviceId)
                    );
                }

                $alias = $tag['alias'];
                $dataProviders[$alias] = $serviceId;
            }

            $this->addServiceToServiceLocator($serviceId);
        }

        return $dataProviders;
    }

    /**
     * @param string $serviceId
     */
    private function addServiceToServiceLocator(string $serviceId): void
    {
        $this->servicesForServiceLocator[$serviceId] = new Reference($serviceId);
    }
}
