<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;

class WorkflowManagerRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager[]|\PHPUnit\Framework\MockObject\MockObject[] */
    protected $managers = [];

    /** @var CurrentApplicationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $applicationProvider;

    /** @var WorkflowManagerRegistry */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->applicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);

        $this->registry = new WorkflowManagerRegistry($this->applicationProvider);
        $this->registry->addManager($this->getWorkflowManager('def'), 'default');
        $this->registry->addManager($this->getWorkflowManager('sys'), 'system');
        $this->registry->addManager($this->getWorkflowManager('m3'), 'manager3');
    }

    public function testGetManager()
    {
        $this->applicationProvider->expects($this->never())->method('getCurrentApplication');

        $this->assertSame($this->getWorkflowManager('m3'), $this->registry->getManager('manager3'));
    }

    public function testGetSystemManager()
    {
        $this->applicationProvider->expects($this->never())->method('getCurrentApplication');

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

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Workflow manager with name "unkonwn" not registered
     */
    public function testGetUnknownManager()
    {
        $this->registry->getManager('unkonwn');
    }

    /**
     * @param string $name
     * @return WorkflowManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getWorkflowManager($name)
    {
        if (!array_key_exists($name, $this->managers)) {
            $this->managers[$name] = $this->getMockBuilder(WorkflowManager::class)
                ->disableOriginalConstructor()->getMock();
        }

        return $this->managers[$name];
    }
}
