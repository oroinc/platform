<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler;

use Oro\Bundle\CacheBundle\Adapter\ChainAdapter;
use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass as CacheConfiguration;
use Oro\Bundle\RedisConfigBundle\DependencyInjection\RedisEnabledCheckTrait;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configure Doctrine related caches.
 */
class DoctrineCacheCompilerPass implements CompilerPassInterface
{
    use RedisEnabledCheckTrait;

    private const DOCTRINE_CACHE_SERVICE           = 'oro.doctrine.abstract';
    private const DOCTRINE_CACHE_NO_MEMORY_SERVICE = 'oro.doctrine.abstract.without_memory_cache';

    public function process(ContainerBuilder $container): void
    {
        if ($this->isRedisEnabledForDoctrine($container)) {
            $abstractCacheDef = $container->getDefinition(self::DOCTRINE_CACHE_SERVICE);
            $chainCacheDef = new Definition(ChainAdapter::class, [
                new Reference('oro.cache.adapter.array'),
                new Reference(self::DOCTRINE_CACHE_SERVICE),
            ]);
            $chainCacheDef->setAbstract(true);
            $container->setDefinition(
                self::DOCTRINE_CACHE_SERVICE,
                $chainCacheDef
            );
            $container->setDefinition(
                self::DOCTRINE_CACHE_NO_MEMORY_SERVICE,
                $abstractCacheDef
            );
            foreach ($this->getDoctrineCacheServices($container) as $serviceId) {
                $serviceDef = $container->getDefinition($serviceId);
                if ($serviceDef instanceof ChildDefinition
                    && str_starts_with($serviceDef->getParent(), CacheConfiguration::DATA_CACHE_POOL)
                ) {
                    $newServiceDef = new ChildDefinition(
                        str_replace(
                            CacheConfiguration::DATA_CACHE_POOL,
                            self::DOCTRINE_CACHE_SERVICE,
                            $serviceDef->getParent()
                        )
                    );
                    $newServiceDef->setArguments([$serviceDef->getArgument(1)]);
                    $newServiceDef->setProperties($serviceDef->getProperties());
                    $newServiceDef->setMethodCalls($serviceDef->getMethodCalls());
                    $newServiceDef->setPublic($serviceDef->isPublic());
                    $newServiceDef->setTags($serviceDef->getTags());
                    $container->setDefinition($serviceId, $newServiceDef);
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    private function getDoctrineCacheServices(ContainerBuilder $container)
    {
        $services = [];
        foreach ($container->getExtensionConfig('doctrine') as $config) {
            if (!empty($config['orm']['entity_managers'])) {
                foreach ($config['orm']['entity_managers'] as $emName => $emConfig) {
                    $key = 'orm|' . $emName;
                    $this->processCacheDriver($services, $key, $emConfig, 'metadata_cache_driver');
                    $this->processCacheDriver($services, $key, $emConfig, 'query_cache_driver');
                }
            }
        }

        return array_unique(array_values($services));
    }

    /**
     * @param array  $services
     * @param string $key
     * @param array  $config
     * @param string $driverType
     */
    private function processCacheDriver(&$services, $key, $config, $driverType)
    {
        if (isset($config[$driverType])) {
            $serviceType = $key . '|' . $driverType;
            if ($this->isCacheDriverService($config[$driverType])) {
                $services[$serviceType] = $config[$driverType]['id'];
            } else {
                unset($services[$serviceType]);
            }
        }
    }

    /**
     * @param mixed $driver
     *
     * @return bool
     */
    private function isCacheDriverService($driver)
    {
        return
            is_array($driver)
            && isset($driver['type'])
            && !$driver['type'] !== 'service'
            && !empty($driver['id']);
    }
}
