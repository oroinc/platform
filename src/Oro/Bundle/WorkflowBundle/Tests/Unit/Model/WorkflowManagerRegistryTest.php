<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkflowManagerRegistryTest extends TestCase
{
    /** @var WorkflowManager[]&MockObject[] */
    private array $managers = [];
    private CurrentApplicationProviderInterface&MockObject $applicationProvider;
    private WorkflowManagerRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->applicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);

        $this->registry = new WorkflowManagerRegistry($this->applicationProvider);
        $this->registry->addManager($this->getWorkflowManager('def'), 'default');
        $this->registry->addManager($this->getWorkflowManager('sys'), 'system');
        $this->registry->addManager($this->getWorkflowManager('m3'), 'manager3');
    }

    public function testGetManager(): void
    {
        $this->applicationProvider->expects($this->never())
            ->method('getCurrentApplication');

        $this->assertSame($this->getWorkflowManager('m3'), $this->registry->getManager('manager3'));
    }

    public function testGetSystemManager(): void
    {
        $this->applicationProvider->expects($this->never())
            ->method('getCurrentApplication');

        $this->assertSame($this->getWorkflowManager('sys'), $this->registry->getManager('system'));
    }

    public function testGetManagerAndDefaultApplication(): void
    {
        $this->applicationProvider->expects($this->once())
            ->method('getCurrentApplication')
            ->willReturn(CurrentApplicationProviderInterface::DEFAULT_APPLICATION);

        $this->assertSame($this->getWorkflowManager('sys'), $this->registry->getManager());
    }

    public function testGetManagerAndCustomApplication(): void
    {
        $this->applicationProvider->expects($this->once())
            ->method('getCurrentApplication')
            ->willReturn('custom');

        $this->assertSame($this->getWorkflowManager('def'), $this->registry->getManager());
    }

    public function testGetUnknownManager(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Workflow manager with name "unkonwn" not registered');

        $this->registry->getManager('unkonwn');
    }

    private function getWorkflowManager(string $name): WorkflowManager&MockObject
    {
        if (!array_key_exists($name, $this->managers)) {
            $this->managers[$name] = $this->createMock(WorkflowManager::class);
        }

        return $this->managers[$name];
    }
}
