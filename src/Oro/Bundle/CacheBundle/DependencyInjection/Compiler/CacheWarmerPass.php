<?php

namespace Oro\Bundle\CacheBundle\DependencyInjection\Compiler;

use Oro\Bundle\CacheBundle\Provider\ConfigCacheWarmerInterface;
use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CacheWarmerPass implements CompilerPassInterface
{
    const SERVICE_ID = 'oro_cache.config_cache_warmer.listener';
    const PROVIDER_TAG = 'oro.config_cache_warmer.provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_ID) || !$container->getParameter('kernel.debug')) {
            return;
        }

        $service = $container->getDefinition(self::SERVICE_ID);
        $cacheProviders = $container->findTaggedServiceIds(self::PROVIDER_TAG);

        foreach ($cacheProviders as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $this->assertInstanceOf($definition, $container, ConfigCacheWarmerInterface::class);

            if (isset($attributes[0]['dumper'])) {
                $dumper = $attributes[0]['dumper'];
            } else {
                throw new \InvalidArgumentException(
                    sprintf('Attribute "dumper" is required for config cache warmer %s', $id)
                );
            }

            $this->assertDumper($container, $dumper);

            $service->addMethodCall(
                'addConfigCacheProvider',
                [new Reference($id), new Reference($dumper)]
            );
        }
    }

    /**
     * @param Definition $definition
     * @param ContainerBuilder $container
     * @param string $class
     */
    protected function assertInstanceOf(Definition $definition, ContainerBuilder $container, $class)
    {
        $className = $definition->getClass();
        if (0 === strpos($className, '%')) {
            $className = $container->getParameterBag()->resolveValue($className);
        }

        if (!class_exists($className)) {
            throw new \InvalidArgumentException(sprintf('The class %s does not exist', $className));
        }

        $reflect = new \ReflectionClass($className);

        if (!$reflect->implementsInterface($class)) {
            throw new \InvalidArgumentException(
                sprintf('The class %s must be implements "%s" interface', $definition->getClass(), $class)
            );
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string $dumper
     */
    protected function assertDumper(ContainerBuilder $container, $dumper)
    {
        if (!$container->hasDefinition($dumper)) {
            throw new \InvalidArgumentException(sprintf('The declared cache metadata dumper "%s" not exist', $dumper));
        }

        $definition = $container->getDefinition($dumper);
        $this->assertInstanceOf($definition, $container, ConfigMetadataDumperInterface::class);
    }
}
