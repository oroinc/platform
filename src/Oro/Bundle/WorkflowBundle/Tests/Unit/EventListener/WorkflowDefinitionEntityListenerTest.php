<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionEntityListener;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowActivationException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowDefinitionEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowDefinitionEntityListener */
    protected $listener;

    /** @var WorkflowRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowRegistry;

    protected function setUp()
    {
        $this->workflowRegistry = $this->getMockBuilder(WorkflowRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WorkflowDefinitionEntityListener($this->workflowRegistry);
    }

    public function testPrePersistNonActiveSkip()
    {
        $definitionMock = $this->getMockBuilder(WorkflowDefinition::class)->disableOriginalConstructor()->getMock();

        $definitionMock->expects($this->once())->method('isActive')->willReturn(false);
        $definitionMock->expects($this->never())->method('hasExclusiveActiveGroups');
        $this->workflowRegistry->expects($this->never())->method('getActiveWorkflowsByActiveGroups');

        $this->listener->prePersist($definitionMock);
    }

    public function testPrePersistNoConflicts()
    {
        $definitionMock = $this->getMockBuilder(WorkflowDefinition::class)->disableOriginalConstructor()->getMock();

        $definitionMock->expects($this->once())->method('isActive')->willReturn(true);
        $definitionMock->expects($this->once())->method('hasExclusiveActiveGroups')->willReturn(true);
        $definitionMock->expects($this->once())->method('getExclusiveActiveGroups')->willReturn(['group1']);
        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByActiveGroups')
            ->willReturn(new ArrayCollection());

        $this->listener->prePersist($definitionMock);
    }

    public function testPrePersistConflictException()
    {
        $definitionMock = $this->getMockBuilder(WorkflowDefinition::class)->getMock();

        $definitionMock->expects($this->once())->method('isActive')->willReturn(true);
        $definitionMock->expects($this->once())->method('hasExclusiveActiveGroups')->willReturn(true);
        $definitionMock->expects($this->any())->method('getExclusiveActiveGroups')->willReturn(['group1']);

        $definitionMock->expects($this->any())->method('getName')->willReturn('workflow1');
        $conflictingDefinitionMock = $this->getMockBuilder(WorkflowDefinition::class)->getMock();
        $conflictingDefinitionMock->expects($this->any())->method('getExclusiveActiveGroups')->willReturn(['group1']);
        $workflowMock = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflowMock->expects($this->once())->method('getDefinition')->willReturn($conflictingDefinitionMock);
        $workflowMock->expects($this->once())->method('getName')->willReturn('conflict_workflow');

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByActiveGroups')
            ->willReturn(new ArrayCollection([$workflowMock]));

        $this->setExpectedException(
            WorkflowActivationException::class,
            'Workflow `workflow1` cannot be activated as it conflicts with' .
            ' workflow `conflict_workflow` by exclusive_active_group `group1`.'
        );

        $this->listener->prePersist($definitionMock);
    }

    public function testPrePersistSeveralConflictsException()
    {
        $definitionMock = $this->getMockBuilder(WorkflowDefinition::class)->getMock();

        $definitionMock->expects($this->once())->method('isActive')->willReturn(true);
        $definitionMock->expects($this->once())->method('hasExclusiveActiveGroups')->willReturn(true);
        $definitionMock->expects($this->any())->method('getExclusiveActiveGroups')->willReturn(['group1', 'group2']);
        $definitionMock->expects($this->any())->method('getName')->willReturn('workflow1');

        $this->workflowRegistry->expects($this->once())->method('getActiveWorkflowsByActiveGroups')->willReturn(
            new ArrayCollection([
                $this->createWorkflow('conflict_workflow1', ['group1']),
                $this->createWorkflow('conflict_workflow2', ['group2'])
            ])
        );

        $this->setExpectedException(
            WorkflowActivationException::class,
            'Workflow `workflow1` cannot be activated as it conflicts with' .
            ' workflow `conflict_workflow1` by exclusive_active_group `group1`,' .
            ' workflow `conflict_workflow2` by exclusive_active_group `group2`.'
        );

        $this->listener->prePersist($definitionMock);
    }

    public function testPreUpdateConflictsException()
    {
        $eventMock = $this->getMockBuilder(PreUpdateEventArgs::class)->disableOriginalConstructor()->getMock();

        $eventMock->expects($this->once())->method('hasChangedField')->with('active')->willReturn(true);
        $eventMock->expects($this->once())->method('getNewValue')->with('active')->willReturn(true);

        $workflow = $this->createWorkflow('workflow1', ['group1']);

        $conflictingWorkflow = $this->createWorkflow('conflict_workflow', ['group1', 'group2']);

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByActiveGroups')->with(['group1'])
            ->willReturn(new ArrayCollection([$workflow, $conflictingWorkflow]));

        $this->setExpectedException(
            WorkflowActivationException::class,
            'Workflow `workflow1` cannot be activated as it conflicts with' .
            ' workflow `conflict_workflow` by exclusive_active_group `group1`.'
        );

        $this->listener->preUpdate($workflow->getDefinition(), $eventMock);
    }

    /**
     * @param string $name
     * @param array $groups
     * @return Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createWorkflow($name, array $groups)
    {
        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $definition = $this->createDefinition($name, $groups);
        $workflow->expects($this->any())->method('getName')->willReturn($name);
        $workflow->expects($this->any())->method('getDefinition')->willReturn($definition);

        return $workflow;
    }

    /**
     * @param $name
     * @param array $groups
     * @return WorkflowDefinition|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createDefinition($name, array $groups)
    {
        $definition = $this->getMockBuilder(WorkflowDefinition::class)->getMock();
        $definition->expects($this->any())->method('getName')->willReturn($name);
        $definition->expects($this->any())->method('getExclusiveActiveGroups')->willReturn($groups);

        return $definition;
    }
}
