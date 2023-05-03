<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

class OroRedisConfigExtension extends Extension implements PrependExtensionInterface
{
    use RedisEnabledCheckTrait;

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        if ($this->isRedisEnabledForCache($container)) {
            $loader->load('cache/services.yml');
        }
        if ($this->isRedisEnabledForDoctrine($container)) {
            $loader->load('doctrine/services.yml');
        }
        if ($this->isRedisEnabledForLayout($container)) {
            $loader->load('layout/services.yml');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        $configs = [[]];

        $fileLocator = new FileLocator(__DIR__ . '/../Resources/config');
        $loader = new Loader\YamlFileLoader($container, $fileLocator);
        $loader->load('services.yml');

        $configs[] = self::parseYmlConfig($fileLocator->locate('session/config.yml'));
        if ($this->isRedisEnabledForCache($container)) {
            $configs[] = self::parseYmlConfig($fileLocator->locate('cache/config.yml'));
        }
        if ($this->isRedisEnabledForDoctrine($container)) {
            $configs[] = self::parseYmlConfig($fileLocator->locate('doctrine/config.yml'));
        }
        if ($this->isRedisEnabledForLayout($container)) {
            $configs[] = self::parseYmlConfig($fileLocator->locate('layout/config.yml'));
        }

        $configs = array_merge_recursive(...$configs);
        foreach ($configs as $name => $config) {
            $container->prependExtensionConfig($name, $config);
        }
    }

    private static function parseYmlConfig(string $filePath): array
    {
        return Yaml::parse(file_get_contents($filePath));
    }
}
