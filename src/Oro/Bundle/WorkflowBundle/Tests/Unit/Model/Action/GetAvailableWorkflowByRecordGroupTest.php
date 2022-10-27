<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Action\GetAvailableWorkflowByRecordGroup;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class GetAvailableWorkflowByRecordGroupTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /** @var GetAvailableWorkflowByRecordGroup */
    private $action;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $this->action = new GetAvailableWorkflowByRecordGroup(new ContextAccessor(), $this->workflowManager);
        $this->action->setDispatcher($eventDispatcher);
    }

    public function testInitializeWithoutGroupName()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Group name parameter is required');

        $this->action->initialize([]);
    }

    public function testInitializeWithoutEntityClass()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Entity class parameter is required');

        $this->action->initialize(['group_name' => 'group1']);
    }

    public function testInitializeWithoutAttribute()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute parameter is required');

        $this->action->initialize(['group_name' => 'group1', 'entity_class' => 'class1']);
    }

    public function testInitializeWithInvalidAttribute()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute must be valid property');

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

    private function createWorkflow(array $recordGroups): Workflow
    {
        $definition = new WorkflowDefinition();
        $definition->setExclusiveRecordGroups($recordGroups);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        return $workflow;
    }
}
