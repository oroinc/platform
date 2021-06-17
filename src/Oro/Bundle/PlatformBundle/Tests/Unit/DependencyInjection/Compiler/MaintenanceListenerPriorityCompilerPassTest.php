<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\MaintenanceListenerPriorityCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MaintenanceListenerPriorityCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var MaintenanceListenerPriorityCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new MaintenanceListenerPriorityCompilerPass();
    }

    public function testProcessNoTags(): void
    {
        $container = new ContainerBuilder();
        $maintenanceListenerDef = $container->register('lexik_maintenance.listener');

        $this->compiler->process($container);

        $this->assertSame([], $maintenanceListenerDef->getTags());
    }

    public function testProcessForKernelEvent(): void
    {
        $container = new ContainerBuilder();
        $maintenanceListenerDef = $container->register('lexik_maintenance.listener')
            ->addTag('kernel.event_listener', ['event' => 'kernel.request']);

        $this->compiler->process($container);

        $this->assertSame(
            ['kernel.event_listener' => [['event' => 'kernel.request', 'priority' => 512]]],
            $maintenanceListenerDef->getTags()
        );
    }

    public function testProcessForNotKernelEvent(): void
    {
        $container = new ContainerBuilder();
        $maintenanceListenerDef = $container->register('lexik_maintenance.listener')
            ->addTag('kernel.event_listener', ['event' => 'sample.event']);

        $this->compiler->process($container);

        $this->assertSame(
            ['kernel.event_listener' => [['event' => 'sample.event']]],
            $maintenanceListenerDef->getTags()
        );
    }
}
