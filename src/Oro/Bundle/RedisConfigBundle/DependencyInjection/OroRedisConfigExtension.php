<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

/**
 * Configures redis based(symfony native functionality) cache adapters and pools that will use them.
 */
class OroRedisConfigExtension extends Extension implements PrependExtensionInterface
{
    use RedisEnabledCheckTrait;

    /** @var FileLocator */
    protected $fileLocator;

    public function __construct()
    {
        $this->fileLocator = new FileLocator(__DIR__ . '/../Resources/config');
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, $this->fileLocator);
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

    public function prepend(ContainerBuilder $container)
    {
        $configs = [[]];

        $loader = new Loader\YamlFileLoader($container, $this->fileLocator);
        $loader->load('services.yml');

        $configs[] = $this->parseYmlConfig($this->fileLocator->locate('session/config.yml'));
        if ($this->isRedisEnabledForCache($container)) {
            $configs[] = $this->parseYmlConfig($this->fileLocator->locate('cache/config.yml'));
        }
        if ($this->isRedisEnabledForDoctrine($container)) {
            $configs[] = $this->parseYmlConfig($this->fileLocator->locate('doctrine/config.yml'));
        }
        if ($this->isRedisEnabledForLayout($container)) {
            $configs[] = $this->parseYmlConfig($this->fileLocator->locate('layout/config.yml'));
        }

        foreach (\array_merge_recursive(...$configs) as $name => $config) {
            $container->prependExtensionConfig($name, $config);
        }
    }

    /**
     * @param string $filePath
     *
     * @return mixed
     */
    public function parseYmlConfig($filePath)
    {
        return Yaml::parse(file_get_contents($filePath));
    }
}
