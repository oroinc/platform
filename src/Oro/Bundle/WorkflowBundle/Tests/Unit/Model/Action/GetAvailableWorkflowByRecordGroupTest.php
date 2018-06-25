<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Action\GetAvailableWorkflowByRecordGroup;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class GetAvailableWorkflowByRecordGroupTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowManager;

    /** @var GetAvailableWorkflowByRecordGroup */
    protected $action;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $this->action = new GetAvailableWorkflowByRecordGroup(new ContextAccessor(), $this->workflowManager);
        $this->action->setDispatcher($eventDispatcher);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Group name parameter is required
     */
    public function testInitializeWithoutGroupName()
    {
        $this->action->initialize([]);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Entity class parameter is required
     */
    public function testInitializeWithoutEntityClass()
    {
        $this->action->initialize(['group_name' => 'group1']);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Attribute parameter is required
     */
    public function testInitializeWithoutAttribute()
    {
        $this->action->initialize(['group_name' => 'group1', 'entity_class' => 'class1']);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Attribute must be valid property
     */
    public function testInitializeWithInvalidAttribute()
    {
        $this->action->initialize(['group_name' => 'group1', 'entity_class' => 'class1', 'attribute' => 'attribute1']);
    }

    public function testExecute()
    {
        $context = (object)['attribute1' => null];

        $options = [
            'group_name' => 'group2',
            'entity_class' => 'class1',
            'attribute' => new PropertyPath('attribute1'),
        ];

        $workflow1 = $this->createWorkflow(['group1', 'group2']);
        $workflow2 = $this->createWorkflow(['group2', 'group3']);
        $workflow3 = $this->createWorkflow(['group3', 'group4']);

        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->with('class1')
            ->willReturn([$workflow1, $workflow2, $workflow3]);

        $this->action->initialize($options);
        $this->action->execute($context);

        $this->assertEquals((object)['attribute1' => $workflow2], $context);
    }

    public function testExecuteWithNullResult()
    {
        $context = (object)['attribute1' => false];

        $options = [
            'group_name' => 'group10',
            'entity_class' => 'class1',
            'attribute' => new PropertyPath('attribute1'),
        ];

        $workflow1 = $this->createWorkflow(['group1', 'group2']);

        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->with('class1')
            ->willReturn([$workflow1]);

        $this->action->initialize($options);
        $this->action->execute($context);

        $this->assertEquals((object)['attribute1' => null], $context);
    }

    /**
     * @param array $recordGroups
     * @return Workflow|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createWorkflow(array $recordGroups)
    {
        $definition = new WorkflowDefinition();
        $definition->setExclusiveRecordGroups($recordGroups);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())->method('getDefinition')->willReturn($definition);

        return $workflow;
    }
}
