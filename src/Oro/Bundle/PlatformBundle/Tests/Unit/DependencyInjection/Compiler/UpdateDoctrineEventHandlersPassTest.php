<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UpdateDoctrineEventHandlersPassTest extends TestCase
{
    private UpdateDoctrineEventHandlersPass $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->compiler = new UpdateDoctrineEventHandlersPass();
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter(
            'doctrine.connections',
            [
                'default'  => 'doctrine.dbal.default_connection',
                'search'   => 'doctrine.dbal.search_connection',
                'security' => 'doctrine.dbal.security_connection'
            ]
        );

        return $container;
    }

    public function testSetDefaultConnectionWhenEmpty(): void
    {
        $container = $this->createContainer();

        $subscriberDef = $container->register('some_subscriber')
            ->addTag('doctrine.event_subscriber');
        $listenerDef = $container->register('some_listener')
            ->addTag('doctrine.event_listener', ['event' => 'preClose'])
            ->addTag('doctrine.event_listener', ['event' => 'onClear']);

        $this->compiler->process($container);

        self::assertEquals(
            [
                'doctrine.event_subscriber' => [
                    ['connection' => 'default']
                ]
            ],
            $subscriberDef->getTags()
        );
        self::assertEquals(
            [
                'doctrine.event_listener' => [
                    ['event' => 'preClose', 'connection' => 'default'],
                    ['event' => 'onClear', 'connection' => 'default']
                ]
            ],
            $listenerDef->getTags()
        );

        self::assertFalse($subscriberDef->isDeprecated());
        self::assertFalse($listenerDef->isDeprecated());
    }

    public function testSetSpecificConnectionWhenNotEmpty(): void
    {
        $container = $this->createContainer();

        $subscriberDef = $container->register('some_subscriber')
            ->addTag('doctrine.event_subscriber', ['connection' => 'security']);
        $listenerDef = $container->register('some_listener')
            ->addTag('doctrine.event_listener', ['event' => 'preClose', 'connection' => 'security'])
            ->addTag('doctrine.event_listener', ['event' => 'onClear', 'connection' => 'security']);

        $this->compiler->process($container);

        self::assertEquals(
            [
                'doctrine.event_subscriber' => [
                    ['connection' => 'security']
                ]
            ],
            $subscriberDef->getTags()
        );
        self::assertEquals(
            [
                'doctrine.event_listener' => [
                    ['event' => 'preClose', 'connection' => 'security'],
                    ['event' => 'onClear', 'connection' => 'security']
                ]
            ],
            $listenerDef->getTags()
        );

        self::assertFalse($subscriberDef->isDeprecated());
        self::assertFalse($listenerDef->isDeprecated());
    }

    public function testMarkDeprecatedWhenDefaultConnectionIsSet(): void
    {
        $container = $this->createContainer();

        $subscriberDef = $container->register('some_subscriber')
            ->addTag('doctrine.event_subscriber', ['connection' => 'default']);
        $listenerDef = $container->register('some_listener')
            ->addTag('doctrine.event_listener', ['event' => 'preClose', 'connection' => 'default'])
            ->addTag('doctrine.event_listener', ['event' => 'onClear', 'connection' => 'default']);

        $this->compiler->process($container);

        self::assertEquals(
            [
                'doctrine.event_subscriber' => [
                    ['connection' => 'default']
                ]
            ],
            $subscriberDef->getTags()
        );
        self::assertEquals(
            [
                'doctrine.event_listener' => [
                    ['event' => 'preClose', 'connection' => 'default'],
                    ['event' => 'onClear', 'connection' => 'default']
                ]
            ],
            $listenerDef->getTags()
        );

        self::assertTrue($subscriberDef->isDeprecated());
        self::assertEquals(
            'Passing "connection: default" to "some_subscriber" tags is default behaviour now.'
            . ' Specify one of "search, security" or remove default one.',
            $subscriberDef->getDeprecation('some_subscriber')['message']
        );
        self::assertTrue($listenerDef->isDeprecated());
        self::assertEquals(
            'Passing "connection: default" to "some_listener" tags is default behaviour now.'
            . ' Specify one of "search, security" or remove default one.',
            $listenerDef->getDeprecation('some_listener')['message']
        );
    }
}
