<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionEntityListener;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowActivationException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowRemoveException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowDefinitionEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowRegistry;

    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $entitiesWithWorkflowsCache;

    /** @var WorkflowDefinitionEntityListener */
    private $listener;

    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->entitiesWithWorkflowsCache = $this->createMock(AbstractAdapter::class);

        $this->listener = new WorkflowDefinitionEntityListener(
            $this->entitiesWithWorkflowsCache,
            $this->workflowRegistry
        );
    }

    public function testPrePersistNonActiveSkip()
    {
        $definitionMock = $this->createMock(WorkflowDefinition::class);

        $definitionMock->expects($this->once())
            ->method('isActive')
            ->willReturn(false);
        $definitionMock->expects($this->never())
            ->method('hasExclusiveActiveGroups');
        $this->workflowRegistry->expects($this->never())
            ->method('getActiveWorkflowsByActiveGroups');

        $this->listener->prePersist($definitionMock);
    }

    public function testPrePersistNoConflicts()
    {
        $definitionMock = $this->createMock(WorkflowDefinition::class);

        $definitionMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $definitionMock->expects($this->once())
            ->method('hasExclusiveActiveGroups')
            ->willReturn(true);
        $definitionMock->expects($this->once())
            ->method('getExclusiveActiveGroups')
            ->willReturn(['group1']);
        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByActiveGroups')
            ->willReturn(new ArrayCollection());

        $this->entitiesWithWorkflowsCache->expects($this->once())
            ->method('clear');

        $this->listener->prePersist($definitionMock);
    }

    public function testPrePersistConflictException()
    {
        $definitionMock = $this->createMock(WorkflowDefinition::class);

        $definitionMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $definitionMock->expects($this->once())
            ->method('hasExclusiveActiveGroups')
            ->willReturn(true);
        $definitionMock->expects($this->any())
            ->method('getExclusiveActiveGroups')
            ->willReturn(['group1']);

        $definitionMock->expects($this->any())
            ->method('getName')
            ->willReturn('workflow1');
        $conflictingDefinitionMock = $this->createMock(WorkflowDefinition::class);
        $conflictingDefinitionMock->expects($this->any())
            ->method('getExclusiveActiveGroups')
            ->willReturn(['group1']);
        $workflowMock = $this->createMock(Workflow::class);
        $workflowMock->expects($this->once())
            ->method('getDefinition')
            ->willReturn($conflictingDefinitionMock);
        $workflowMock->expects($this->once())
            ->method('getName')
            ->willReturn('conflict_workflow');

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByActiveGroups')
            ->willReturn(new ArrayCollection([$workflowMock]));

        $this->expectException(WorkflowActivationException::class);
        $this->expectExceptionMessage(
            'Workflow `workflow1` cannot be activated as it conflicts with' .
            ' workflow `conflict_workflow` by exclusive_active_group `group1`.'
        );

        $this->listener->prePersist($definitionMock);
    }

    public function testPrePersistSeveralConflictsException()
    {
        $definitionMock = $this->createMock(WorkflowDefinition::class);

        $definitionMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $definitionMock->expects($this->once())
            ->method('hasExclusiveActiveGroups')
            ->willReturn(true);
        $definitionMock->expects($this->any())
            ->method('getExclusiveActiveGroups')
            ->willReturn(['group1', 'group2']);
        $definitionMock->expects($this->any())
            ->method('getName')
            ->willReturn('workflow1');

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByActiveGroups')
            ->willReturn(
                new ArrayCollection([
                    $this->createWorkflow('conflict_workflow1', ['group1']),
                    $this->createWorkflow('conflict_workflow2', ['group2'])
                ])
            );

        $this->expectException(WorkflowActivationException::class);
        $this->expectExceptionMessage(
            'Workflow `workflow1` cannot be activated as it conflicts with' .
            ' workflow `conflict_workflow1` by exclusive_active_group `group1`,' .
            ' workflow `conflict_workflow2` by exclusive_active_group `group2`.'
        );

        $this->listener->prePersist($definitionMock);
    }

    public function testPreUpdateChangedRelatedEntity()
    {
        $event = $this->createMock(PreUpdateEventArgs::class);

        $event->expects($this->any())
            ->method('hasChangedField')
            ->willReturnMap([
                ['active', false],
                ['relatedEntity', true]
            ]);

        $workflow = $this->createWorkflow('workflow1', ['group1']);

        $this->entitiesWithWorkflowsCache->expects($this->once())
            ->method('clear');

        $this->listener->preUpdate($workflow->getDefinition(), $event);
    }

    public function testPreUpdateChangedIsActive()
    {
        $event = $this->createMock(PreUpdateEventArgs::class);

        $event->expects($this->any())
            ->method('hasChangedField')
            ->willReturnMap([
                ['active', true],
                ['relatedEntity', false]
            ]);
        $event->expects($this->once())
            ->method('getNewValue')
            ->with('active')
            ->willReturn(true);

        $workflow = $this->createWorkflow('workflow1', ['group1']);
        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByActiveGroups')
            ->with(['group1'])
            ->willReturn(new ArrayCollection([$workflow]));

        $this->entitiesWithWorkflowsCache->expects($this->once())
            ->method('clear');

        $this->listener->preUpdate($workflow->getDefinition(), $event);
    }

    public function testPreUpdateChangedNotTrackedProperty()
    {
        $event = $this->createMock(PreUpdateEventArgs::class);

        $event->expects($this->any())
            ->method('hasChangedField')
            ->willReturnMap([
                ['active', false],
                ['relatedEntity', false]
            ]);

        $workflow = $this->createWorkflow('workflow1', ['group1']);

        $this->entitiesWithWorkflowsCache->expects($this->never())
            ->method('clear');

        $this->listener->preUpdate($workflow->getDefinition(), $event);
    }

    public function testPreUpdateConflictsException()
    {
        $eventMock = $this->createMock(PreUpdateEventArgs::class);

        $eventMock->expects($this->once())
            ->method('hasChangedField')
            ->with('active')
            ->willReturn(true);
        $eventMock->expects($this->once())
            ->method('getNewValue')
            ->with('active')
            ->willReturn(true);

        $workflow = $this->createWorkflow('workflow1', ['group1']);

        $conflictingWorkflow = $this->createWorkflow('conflict_workflow', ['group1', 'group2']);

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByActiveGroups')
            ->with(['group1'])
            ->willReturn(new ArrayCollection([$workflow, $conflictingWorkflow]));

        $this->expectException(WorkflowActivationException::class);
        $this->expectExceptionMessage(
            'Workflow `workflow1` cannot be activated as it conflicts with' .
            ' workflow `conflict_workflow` by exclusive_active_group `group1`.'
        );

        $this->entitiesWithWorkflowsCache->expects($this->never())
            ->method('clear');

        $this->listener->preUpdate($workflow->getDefinition(), $eventMock);
    }

    public function testPreRemoveSystemWorkflowException()
    {
        $this->expectException(WorkflowRemoveException::class);
        $this->expectExceptionMessage("Workflow 'workflow1' can't be removed due its System workflow");

        $definitionMock = $this->createMock(WorkflowDefinition::class);
        $definitionMock->expects($this->once())
            ->method('isSystem')
            ->willReturn(true);
        $definitionMock->expects($this->once())
            ->method('getName')
            ->willReturn('workflow1');

        $this->listener->preRemove($definitionMock);
    }

    public function testPreRemoveSystemWorkflow()
    {
        $definitionMock = $this->createMock(WorkflowDefinition::class);
        $definitionMock->expects($this->once())
            ->method('isSystem')
            ->willReturn(false);
        $definitionMock->expects($this->never())
            ->method('getName');

        $this->entitiesWithWorkflowsCache->expects($this->once())
            ->method('clear');

        $this->listener->preRemove($definitionMock);
    }

    private function createWorkflow(string $name, array $groups): Workflow
    {
        $workflow = $this->createMock(Workflow::class);
        $definition = $this->createDefinition($name, $groups);
        $workflow->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $workflow->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        return $workflow;
    }

    private function createDefinition(string $name, array $groups): WorkflowDefinition
    {
        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $definition->expects($this->any())
            ->method('getExclusiveActiveGroups')
            ->willReturn($groups);

        return $definition;
    }
}
