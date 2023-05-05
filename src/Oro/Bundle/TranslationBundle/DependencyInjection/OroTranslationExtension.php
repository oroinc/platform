<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroTranslationExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $container->prependExtensionConfig($this->getAlias(), SettingsBuilder::getSettings($config));

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('form_types.yml');
        $loader->load('services.yml');
        $loader->load('importexport.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
        $loader->load('controllers_api.yml');
        $loader->load('mq_topics.yml');

        $container->getDefinition('oro_translation.js_generator')
            ->setArgument('$domains', $config['js_translation']['domains']);
        $container->getDefinition('oro_translation.manager.translation')
            ->setArgument('$jsTranslationDomains', $config['js_translation']['domains']);
        $container->getDefinition('oro_translation.twig.translation.extension')
            ->setArgument('$isDebugJsTranslations', $config['js_translation']['debug']);

        $container->setParameter(
            'oro_translation.translation_service.apikey',
            $config['translation_service']['apikey']
        );

        $container->setParameter('oro_translation.package_names', array_unique($config['package_names']));
        $container->setParameter('oro_translation.debug_translator', $config['debug_translator']);
        $container->setParameter('oro_translation.locales', $config['locales']);
        $container->setParameter('oro_translation.default_required', $config['default_required']);
        $container->setParameter('oro_translation.templating', $config['templating']);

        $this->configureTranslatableDictionaries($container, $config['translatable_dictionaries']);
    }

    private function configureTranslatableDictionaries(ContainerBuilder $container, array $config): void
    {
        $listenerDef = $container->getDefinition('oro_translation.event_listener.update_translatable_dictionaries');
        foreach ($config as $entityClass => $fields) {
            foreach ($fields as $translatableFieldName => $fieldConfig) {
                $listenerDef->addMethodCall('addEntity', [
                    $entityClass,
                    $translatableFieldName,
                    $fieldConfig['translation_key_prefix'],
                    $fieldConfig['key_field_name']
                ]);
            }
        }
    }
}
