<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class UpdateDoctrineEventHandlersPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var UpdateDoctrineEventHandlersPass */
    protected $compiler;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder */
    protected $container;

    protected function setUp(): void
    {
        $this->compiler = new UpdateDoctrineEventHandlersPass();
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()->getMock();

        $this->container->expects($this->any())->method('hasParameter')->willReturn(true);
        $this->container->expects($this->any())->method('getParameter')
            ->willReturn(['search' => 'doctrine.dbal.search_connection']);
    }

    public function testSetDefaultConnectionWhenEmpty()
    {
        $this->container->expects($this->any())->method('findTaggedServiceIds')
            ->willReturn(
                [
                    'oro_security.listener.refresh_context_listener' => [
                        ['event' => 'preClose', 'lazy' => true],
                        ['event' => 'onClear', 'lazy' => true],
                    ],
                ]
            );

        $definition = new Definition();
        $this->container->expects($this->any())->method('getDefinition')->willReturn($definition);
        $this->compiler->process($this->container);

        $this->assertEquals(
            [
                'doctrine.event_subscriber' => [
                    ['event' => 'preClose', 'lazy' => true, 'connection' => 'default'],
                    ['event' => 'onClear', 'lazy' => true, 'connection' => 'default'],
                ],
                'doctrine.event_listener' => [
                    ['event' => 'preClose', 'lazy' => true, 'connection' => 'default'],
                    ['event' => 'onClear', 'lazy' => true, 'connection' => 'default'],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testSetSpecificConnectionWhenNotEmpty()
    {
        $this->container->expects($this->any())->method('findTaggedServiceIds')
            ->willReturn(
                [
                    'oro_security.listener.refresh_context_listener' => [
                        ['event' => 'preClose', 'lazy' => true, 'connection' => 'security'],
                        ['event' => 'onClear', 'lazy' => true, 'connection' => 'security'],
                    ],
                ]
            );

        $definition = new Definition();
        $this->container->expects($this->any())->method('getDefinition')->willReturn($definition);
        $this->compiler->process($this->container);

        $this->assertEquals(
            [
                'doctrine.event_subscriber' => [
                    ['event' => 'preClose', 'lazy' => true, 'connection' => 'security'],
                    ['event' => 'onClear', 'lazy' => true, 'connection' => 'security'],
                ],
                'doctrine.event_listener' => [
                    ['event' => 'preClose', 'lazy' => true, 'connection' => 'security'],
                    ['event' => 'onClear', 'lazy' => true, 'connection' => 'security'],
                ],
            ],
            $definition->getTags()
        );
    }
}
