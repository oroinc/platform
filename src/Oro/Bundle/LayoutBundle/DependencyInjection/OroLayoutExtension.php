<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroLayoutExtension extends Extension implements PrependExtensionInterface
{
    private const RESOURCES_FOLDER_PATTERN = '[a-zA-Z][a-zA-Z0-9_\-:]*';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $container->prependExtensionConfig($this->getAlias(), SettingsBuilder::getSettings($config));

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('block_types.yml');
        $loader->load('collectors.yml');
        $loader->load('commands.yml');
        $loader->load('image_placeholder.yml');

        if ($config['view']['annotations']) {
            $loader->load('view_annotations.yml');
        }

        $container->setParameter(
            'oro_layout.templating.default',
            $config['templating']['default']
        );
        $container->setParameter(
            'oro_layout.enabled_themes',
            $config['enabled_themes']
        );
        $loader->load('twig_renderer.yml');
        $container->setParameter(
            'oro_layout.twig.resources',
            $config['templating']['twig']['resources']
        );

        $loader->load('theme_services.yml');
        if (isset($config['active_theme'])) {
            $container->setParameter('oro_layout.default_active_theme', $config['active_theme']);
        }
        $container->setParameter('oro_layout.debug', $config['debug']);

        $container->getDefinition('oro_layout.theme_extension.resource_provider.theme')
            ->replaceArgument(5, $this->getExcludePatterns());
        $container->getDefinition('oro_layout.theme_extension.configuration.provider')
            ->replaceArgument(3, self::RESOURCES_FOLDER_PATTERN);

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        if ('test' === $container->getParameter('kernel.environment')) {
            $path = dirname(__DIR__) . '/Tests/Functional/Layout';
            $container->prependExtensionConfig('twig', ['paths' => [$path => 'OroLayoutBundleStub']]);
        }
    }

    /**
     * @return string[]
     */
    private function getExcludePatterns(): array
    {
        return [
            '#Resources/views/layouts/' . self::RESOURCES_FOLDER_PATTERN . '/theme\.yml$#',
            '#Resources/views/layouts/' . self::RESOURCES_FOLDER_PATTERN . '/config/[^/]+\.yml$#',
            '#templates/layouts/' . self::RESOURCES_FOLDER_PATTERN . '/theme\.yml$#',
            '#templates/layouts/' . self::RESOURCES_FOLDER_PATTERN . '/config/[^/]+\.yml$#'
        ];
    }
}
