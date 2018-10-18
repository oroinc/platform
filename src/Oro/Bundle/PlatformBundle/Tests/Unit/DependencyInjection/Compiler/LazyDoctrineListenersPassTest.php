<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\LazyDoctrineListenersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LazyDoctrineListenersPassTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldMarkDoctrineEventListenersAsLazyAndPublic()
    {
        $container = new ContainerBuilder();
        $container->register('public_listener')
            ->addTag('doctrine.event_listener');
        $container->register('private_listener')
            ->setPublic(false)
            ->addTag('doctrine.event_listener');
        $container->register('lazy_listener')
            ->addTag('doctrine.event_listener', ['lazy' => true]);
        $container->register('not_lazy_listener')
            ->addTag('doctrine.event_listener', ['lazy' => false]);
        $container->register('private_lazy_listener')
            ->setPublic(false)
            ->addTag('doctrine.event_listener', ['lazy' => true]);
        $container->register('private_not_lazy_listener')
            ->setPublic(false)
            ->addTag('doctrine.event_listener', ['lazy' => false]);
        $container->register('listener_with_several_tags')
            ->addTag('another_tag')
            ->addTag('doctrine.event_listener', ['event' => 'prePersist'])
            ->addTag('doctrine.event_listener', ['event' => 'postPersist']);

        $compiler = new LazyDoctrineListenersPass();
        $compiler->process($container);

        self::assertEquals(
            ['doctrine.event_listener' => [['lazy' => true]]],
            $container->getDefinition('public_listener')->getTags()
        );
        self::assertEquals(
            ['doctrine.event_listener' => [['lazy' => true]]],
            $container->getDefinition('private_listener')->getTags()
        );
        self::assertEquals(
            ['doctrine.event_listener' => [['lazy' => true]]],
            $container->getDefinition('lazy_listener')->getTags()
        );
        self::assertEquals(
            ['doctrine.event_listener' => [['lazy' => false]]],
            $container->getDefinition('not_lazy_listener')->getTags()
        );
        self::assertEquals(
            ['doctrine.event_listener' => [['lazy' => true]]],
            $container->getDefinition('private_lazy_listener')->getTags()
        );
        self::assertEquals(
            ['doctrine.event_listener' => [['lazy' => false]]],
            $container->getDefinition('private_not_lazy_listener')->getTags()
        );
        self::assertEquals(
            [
                'doctrine.event_listener' => [
                    ['event' => 'prePersist', 'lazy' => true],
                    ['event' => 'postPersist', 'lazy' => true]
                ],
                'another_tag'             => [
                    []
                ]
            ],
            $container->getDefinition('listener_with_several_tags')->getTags()
        );

        self::assertTrue($container->getDefinition('public_listener')->isPublic());
        self::assertTrue($container->getDefinition('private_listener')->isPublic());
        self::assertTrue($container->getDefinition('lazy_listener')->isPublic());
        self::assertTrue($container->getDefinition('not_lazy_listener')->isPublic());
        self::assertTrue($container->getDefinition('private_lazy_listener')->isPublic());
        self::assertFalse($container->getDefinition('private_not_lazy_listener')->isPublic());
        self::assertTrue($container->getDefinition('listener_with_several_tags')->isPublic());
    }
}
