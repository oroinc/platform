<?php

namespace Oro\Bundle\SSOBundle\DependencyInjection;

use Oro\Bundle\DistributionBundle\DependencyInjection\OroContainerBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroSSOExtension extends Extension implements PrependExtensionInterface
{
    /**
     * Load
     *
     * @param  array            $configs
     * @param  ContainerBuilder $container
     * @throws InvalidConfigurationException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $serviceLoader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $serviceLoader->load('services.yml');

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return 'oro_sso';
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if (!$container instanceof OroContainerBuilder) {
            throw new \RuntimeException('Oro\Bundle\DistributionBundle\DependencyInjection\OroContainerBuilder is expected to be passed into OroSSOExtension');
        }

        $originalConfig = $container->getExtensionConfig('security');
        if (!count($originalConfig)) {
            $originalConfig[] = array();
        }

        $oautConfig = [
            'firewalls' => [
                'main' => [
                    'oauth' => [
                        'resource_owners' => [
                            'google' => '/login/check-google',
                        ],
                        'login_path' => '/user/login',
                        'failure_path' => '/user/login',
                        'oauth_user_provider'=> [
                            'service' => 'oro_sso.oauth_provider',
                        ],
                    ],
                ],
            ],
        ];

        $mergedConfig = [array_merge_recursive($originalConfig[0], $oautConfig)];

        $container->setExtensionConfig('security', $mergedConfig);
    }
}
