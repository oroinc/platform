<?php

namespace Oro\Bundle\FormBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroFormExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('autocomplete.yml');
        $loader->load('form_type.yml');
        $loader->load('importexport.yml');
        $loader->load('services.yml');

        if (isset($config['wysiwyg']['html_allowed_elements'])) {
            $definition = $container->getDefinition('oro_form.provider.html_tag_provider');
            $definition->replaceArgument(0, $config['wysiwyg']['html_allowed_elements']);
        }

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }
}
