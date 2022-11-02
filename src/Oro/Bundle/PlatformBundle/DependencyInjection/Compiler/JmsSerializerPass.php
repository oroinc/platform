<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * When JMSSerializerBundle is installed, substitute a serializer Twig extension added by this bundle
 * with our extension that does not initializes JMS serializer service on each web request.
 *
 * When JMSSerializerBundle is not installed, remove "fos_rest.serializer.flatten_exception_handler" service.
 * It fixes a bug in FosRestBundle, this bundle adds this service even if JMSSerializerBundle is not installed.
 */
class JmsSerializerPass implements CompilerPassInterface
{
    public const JMS_SERIALIZER_CACHE_ADAPTER_SERVICE_ID = 'oro_platform.jms_serializer_cache_adapter';

    private const FOS_REST_JMS_SERIALIZER_FLATTEN_EXCEPTION_HANDLER = 'fos_rest.serializer.flatten_exception_handler';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('jms_serializer.serializer')) {
            $this->substituteJmsSerializerTwigExtension($container);
            $this->configureJmsSerializerCache($container);
        } else {
            if (!$container->hasDefinition(self::FOS_REST_JMS_SERIALIZER_FLATTEN_EXCEPTION_HANDLER)) {
                throw new \LogicException(sprintf(
                    'The service "%s" does not exist. May be the bug with the registration of this service'
                    . ' was fixed in FosRestBundle and the fix can be removed from the "%s"?',
                    self::FOS_REST_JMS_SERIALIZER_FLATTEN_EXCEPTION_HANDLER,
                    __CLASS__
                ));
            }
            $container->removeDefinition(self::FOS_REST_JMS_SERIALIZER_FLATTEN_EXCEPTION_HANDLER);
        }
    }

    private function substituteJmsSerializerTwigExtension(ContainerBuilder $container): void
    {
        $container->getDefinition('jms_serializer.twig_extension.serializer')
            ->setClass('Oro\Bundle\PlatformBundle\Twig\SerializerExtension')
            ->setArguments([new Reference('oro_platform.twig.service_locator')]);
    }

    private function configureJmsSerializerCache(ContainerBuilder $container): void
    {
        $cacheServiceId = 'oro_platform.jms_serializer.cache';
        $cacheNamespace = 'jms_serializer_cache';
        $container->setDefinition($cacheServiceId, new ChildDefinition('oro.data.cache'))
            ->setPublic(false)
            ->addTag('cache.pool', ['namespace' => $cacheNamespace]);
        $container->register(self::JMS_SERIALIZER_CACHE_ADAPTER_SERVICE_ID, 'Metadata\Cache\PsrCacheAdapter')
            ->setPublic(false)
            ->setArguments([$cacheNamespace, new Reference($cacheServiceId)]);
    }
}
