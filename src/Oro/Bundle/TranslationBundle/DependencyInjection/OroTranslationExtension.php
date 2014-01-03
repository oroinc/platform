<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;

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

        $container
            ->getDefinition('oro_translation.controller')
            ->replaceArgument(3, $config['js_translation']);

        $container->setParameter('oro_translation.js_translation.domains', $config['js_translation']['domains']);

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

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }
}
