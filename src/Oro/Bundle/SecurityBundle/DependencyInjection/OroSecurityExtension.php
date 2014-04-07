<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

use Oro\Bundle\SecurityBundle\Annotation\Loader\AclAnnotationCumulativeResourceLoader;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroSecurityExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        self::getAclConfigLoader()->registerResources($container);
        self::getAclAnnotationLoader()->registerResources($container);

        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('ownership.yml');
        $loader->load('services.yml');
    }

    /**
     * @return CumulativeConfigLoader
     */
    public static function getAclConfigLoader()
    {
        return new CumulativeConfigLoader(
            'oro_acl_config',
            new YamlCumulativeFileLoader('Resources/config/acl.yml')
        );
    }

    /**
     * @return CumulativeConfigLoader
     */
    public static function getAclAnnotationLoader()
    {
        return new CumulativeConfigLoader(
            'oro_acl_annotation',
            new AclAnnotationCumulativeResourceLoader(['Controller'])
        );
    }
}
