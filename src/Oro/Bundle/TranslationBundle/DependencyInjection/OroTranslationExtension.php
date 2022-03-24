<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Loads bundle configuration and configures DI container.
 */
class OroTranslationExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('form_types.yml');
        $loader->load('services.yml');
        $loader->load('importexport.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
        $loader->load('controllers_api.yml');
        $loader->load('mq_topics.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }

        $container->getDefinition('oro_translation.js_generator')
            ->setArgument('$domains', $config['js_translation']['domains']);
        $container->getDefinition('oro_translation.twig.translation.extension')
            ->setArgument('$isDebugJsTranslations', $config['js_translation']['debug']);

        $container->setParameter(
            'oro_translation.translation_service.apikey',
            $config['translation_service']['apikey']
        );

        $container->setParameter('oro_translation.package_names', \array_unique($config['package_names']));

        $container->setParameter('oro_translation.debug_translator', $config['debug_translator']);
        $container->setParameter('oro_translation.locales', $config['locales']);
        $container->setParameter('oro_translation.default_required', $config['default_required']);
        $container->setAlias('oro_translation.manager_registry', $config['manager_registry']);
        $container->setParameter('oro_translation.templating', $config['templating']);

        $container->prependExtensionConfig($this->getAlias(), \array_intersect_key($config, \array_flip(['settings'])));
    }
}
