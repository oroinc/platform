<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;

class WorkflowManagerRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager[]|\PHPUnit\Framework\MockObject\MockObject[] */
    private $managers = [];

    /** @var CurrentApplicationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $applicationProvider;

    /** @var WorkflowManagerRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->applicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);

        $this->registry = new WorkflowManagerRegistry($this->applicationProvider);
        $this->registry->addManager($this->getWorkflowManager('def'), 'default');
        $this->registry->addManager($this->getWorkflowManager('sys'), 'system');
        $this->registry->addManager($this->getWorkflowManager('m3'), 'manager3');
    }

    public function testGetManager()
    {
        $this->applicationProvider->expects($this->never())
            ->method('getCurrentApplication');

        $this->assertSame($this->getWorkflowManager('m3'), $this->registry->getManager('manager3'));
    }

    public function testGetSystemManager()
    {
        $this->applicationProvider->expects($this->never())
            ->method('getCurrentApplication');

        $this->assertSame($this->getWorkflowManager('sys'), $this->registry->getManager('system'));
    }

    public function testGetManagerAndDefaultApplication()
    {
        $this->applicationProvider->expects($this->once())
            ->method('getCurrentApplication')
            ->willReturn(CurrentApplicationProviderInterface::DEFAULT_APPLICATION);

        $this->assertSame($this->getWorkflowManager('sys'), $this->registry->getManager());
    }

    public function testGetManagerAndCustomApplication()
    {
        $this->applicationProvider->expects($this->once())
            ->method('getCurrentApplication')
            ->willReturn('custom');

        $this->assertSame($this->getWorkflowManager('def'), $this->registry->getManager());
    }

    public function testGetUnknownManager()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Workflow manager with name "unkonwn" not registered');

        $this->registry->getManager('unkonwn');
    }

    /**
     * @param string $name
     * @return WorkflowManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getWorkflowManager($name)
    {
        if (!array_key_exists($name, $this->managers)) {
            $this->managers[$name] = $this->createMock(WorkflowManager::class);
        }

        return $this->managers[$name];
    }
}
