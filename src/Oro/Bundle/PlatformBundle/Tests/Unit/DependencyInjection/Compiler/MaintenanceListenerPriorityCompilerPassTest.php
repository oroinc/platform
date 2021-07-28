<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\MaintenanceListenerPriorityCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MaintenanceListenerPriorityCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var MaintenanceListenerPriorityCompilerPass */
    private $compiler;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder */
    private $container;

    protected function setUp(): void
    {
        $this->compiler = new MaintenanceListenerPriorityCompilerPass();
        $this->container = $this->createMock(ContainerBuilder::class);
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(array $tags, array $expectedTags): void
    {
        $definition = new Definition();
        $definition->setTags($tags);

        $this->container
            ->expects($this->once())
            ->method('getDefinition')
            ->with('lexik_maintenance.listener')
            ->willReturn($definition);

        $this->compiler->process($this->container);

        $this->assertEquals($expectedTags, $definition->getTags());
    }

    public function processDataProvider(): array
    {
        return [
            [
                'tags' => ['kernel.event_listener' => [['event' => 'kernel.request']]],
                'expectedTags' => ['kernel.event_listener' => [['event' => 'kernel.request', 'priority' => 512]]],
            ],
            [
                'tags' => ['kernel.event_listener' => [['event' => 'sample.event']]],
                'expectedTags' => ['kernel.event_listener' => [['event' => 'sample.event']]],
            ],
            [
                'tags' => [],
                'expectedTags' => [],
            ],
        ];
    }
}
