<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroEmailExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter(
            'oro_email.email_sync_exclusions',
            $config['email_sync_exclusions']
        );

        $container->setParameter(
            'oro_email.flash_notification.max_emails_display',
            $config['flash_notification']['max_emails_display']
        );

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('services_api.yml');
        $loader->load('form.yml');
        $loader->load('mass_action.yml');
        $loader->load('filters.yml');
        $loader->load('commands.yml');

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        // X-Frame-Options header should be removed from embedded forms
        $securityConfig = $container->getExtensionConfig('nelmio_security');

        $emailTemplatePreviewPath = [
            '/email/emailtemplate/preview' => 'ALLOW',
        ];

        if (isset($securityConfig[0]['clickjacking']['paths'])
            && is_array($securityConfig[0]['clickjacking']['paths'])) {
            $securityConfig[0]['clickjacking']['paths']
                = $emailTemplatePreviewPath + $securityConfig[0]['clickjacking']['paths'];
        }

        /** @var ExtendedContainerBuilder $container */
        $container->setExtensionConfig('nelmio_security', $securityConfig);
    }
}
