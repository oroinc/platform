<?php

namespace Oro\Bundle\FormBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroFormExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $container->prependExtensionConfig($this->getAlias(), SettingsBuilder::getSettings($config));

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('autocomplete.yml');
        $loader->load('form_type.yml');
        $loader->load('importexport.yml');
        $loader->load('services.yml');
        $loader->load('controllers.yml');
        $loader->load('controllers_api.yml');

        if (isset($config['html_purifier_modes'])) {
            $container->getDefinition('oro_form.provider.html_tag_provider')
                ->replaceArgument(0, $config['html_purifier_modes']);
        }
    }
}
