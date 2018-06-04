<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroTranslationExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('form_types.yml');
        $loader->load('services.yml');
        $loader->load('importexport.yml');
        $loader->load('commands.yml');

        $container
            ->getDefinition('oro_translation.controller')
            ->replaceArgument(3, $config['js_translation']);

        $container->setParameter('oro_translation.js_translation.domains', $config['js_translation']['domains']);
        $container->setParameter('oro_translation.js_translation.debug', $config['js_translation']['debug']);

        $container->setParameter('oro_translation.locales', $config['locales']);
        $container->setParameter('oro_translation.default_required', $config['default_required']);
        $container->setAlias('oro_translation.manager_registry', $config['manager_registry']);
        $container->setParameter('oro_translation.templating', $config['templating']);

        if (!empty($config['api'])) {
            foreach ($config['api'] as $serviceId => $params) {
                foreach ($params as $key => $value) {
                    $container->setParameter(
                        sprintf('oro_translation.api.%s.%s', $serviceId, $key),
                        $value
                    );
                }
            }
        }

        $serviceId = sprintf('oro_translation.uploader.%s_adapter', $config['default_api_adapter']);
        if ($container->has($serviceId)) {
            $container->setAlias('oro_translation.uploader.default_adapter', $serviceId);
        }

        $container->setParameter('oro_translation.debug_translator', $config['debug_translator']);

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }
}
