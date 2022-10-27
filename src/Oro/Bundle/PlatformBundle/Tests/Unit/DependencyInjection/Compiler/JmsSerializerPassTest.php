<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Metadata\Cache\PsrCacheAdapter;
use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\JmsSerializerPass;
use Oro\Bundle\PlatformBundle\Twig\SerializerExtension;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class JmsSerializerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var JmsSerializerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new JmsSerializerPass();
    }

    public function testProcessWhenJmsSerializerBundleIsNotInstalled(): void
    {
        $container = new ContainerBuilder();
        $container->register('fos_rest.serializer.flatten_exception_handler');

        $this->compiler->process($container);

        self::assertFalse($container->hasDefinition('jms_serializer.twig_extension.serializer'));
        self::assertFalse($container->hasDefinition('oro_platform.jms_serializer.cache'));
        self::assertFalse($container->hasDefinition('oro_platform.jms_serializer_cache_adapter'));
        self::assertFalse($container->hasDefinition('fos_rest.serializer.flatten_exception_handler'));
    }

    public function testProcessWhenJmsSerializerBundleIsNotInstalledAndNoFosRestFlattenExceptionHandler(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The service "fos_rest.serializer.flatten_exception_handler" does not exist.'
            . ' May be the bug with the registration of this service was fixed in FosRestBundle'
            . ' and the fix can be removed from the "%s"?',
            JmsSerializerPass::class
        ));

        $container = new ContainerBuilder();
        $this->compiler->process($container);
    }

    public function testProcessWhenJmsSerializerBundleIsInstalled(): void
    {
        $container = new ContainerBuilder();
        $container->register('jms_serializer.serializer');
        $serializerTwigExtension = $container
            ->register('jms_serializer.twig_extension.serializer', 'JmsSerializerExtension')
            ->addArgument(new Reference('jms_serializer.serializer'));

        $this->compiler->process($container);

        self::assertEquals(SerializerExtension::class, $serializerTwigExtension->getClass());
        self::assertEquals(
            [new Reference('oro_platform.twig.service_locator')],
            $serializerTwigExtension->getArguments()
        );

        $jmsSerializerCacheDef = (new ChildDefinition('oro.data.cache'))
            ->setPublic(false)
            ->addTag('cache.pool', ['namespace' => 'jms_serializer_cache']);
        self::assertEquals(
            $jmsSerializerCacheDef,
            $container->getDefinition('oro_platform.jms_serializer.cache')
        );

        $jmsSerializerCacheAdapterDef = (new Definition(PsrCacheAdapter::class))
            ->setPublic(false)
            ->setArguments([
                'jms_serializer_cache',
                new Reference('oro_platform.jms_serializer.cache')
            ]);
        self::assertEquals(
            $jmsSerializerCacheAdapterDef,
            $container->getDefinition('oro_platform.jms_serializer_cache_adapter')
        );
    }
}
