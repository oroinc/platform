<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Parser;

class OroAttachmentExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('services_api.yml');
        $loader->load('form.yml');

        $container->setParameter('oro_attachment.debug_images', $config['debug_images']);
        $container->setParameter('oro_attachment.upload_file_mime_types', $config['upload_file_mime_types']);
        $container->setParameter('oro_attachment.upload_image_mime_types', $config['upload_image_mime_types']);

        $yaml  = new Parser();
        $value = $yaml->parse(file_get_contents(__DIR__ . '/../Resources/config/files.yml'));
        $container->setParameter('oro_attachment.files', $value['file-icons']);

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }
}
