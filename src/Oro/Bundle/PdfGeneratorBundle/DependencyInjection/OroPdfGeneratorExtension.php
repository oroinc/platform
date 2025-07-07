<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroPdfGeneratorExtension extends Extension
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        if (empty($config['default_engine'])) {
            throw new InvalidConfigurationException(
                'Setting "' . Configuration::ROOT_NODE . '.default_engine" must be not empty.'
            );
        }

        $container->setParameter('oro_pdf_generator.pdf_engine_name', $config['default_engine']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('controllers.yml');
        $loader->load('services.yml');
        $loader->load('services_pdf_document.yml');

        if (class_exists('Gotenberg\Gotenberg')) {
            $loader->load('services_gotenberg.yml');

            if (empty($config['engines']['gotenberg']['api_url'])) {
                throw new InvalidConfigurationException(
                    'Setting "' . Configuration::ROOT_NODE . '.engines.gotenberg.api_url" must be not empty.'
                );
            }

            $container->setParameter(
                'oro_pdf_generator.gotenberg.api_url',
                $config['engines']['gotenberg']['api_url']
            );
        }
    }
}
