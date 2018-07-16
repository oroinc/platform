<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionEntityListener;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowActivationException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowDefinitionEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowDefinitionEntityListener */
    protected $listener;

    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowRegistry;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $entitiesWithWorkflowsCache;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    protected function setUp()
    {
        $this->workflowRegistry = $this->getMockBuilder(WorkflowRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entitiesWithWorkflowsCache = $this->getMockBuilder(CacheProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = TestContainerBuilder::create()
            ->add('oro_workflow.registry.system', $this->workflowRegistry)
            ->add('oro_workflow.cache.entities_with_workflow', $this->entitiesWithWorkflowsCache)
            ->getContainer($this);

        $this->listener = new WorkflowDefinitionEntityListener($this->container);
    }

    public function testPrePersistNonActiveSkip()
    {
        $definitionMock = $this->getMockBuilder(WorkflowDefinition::class)->disableOriginalConstructor()->getMock();

        $definitionMock->expects($this->once())->method('isActive')->willReturn(false);
        $definitionMock->expects($this->never())->method('hasExclusiveActiveGroups');
        $this->workflowRegistry->expects($this->never())->method('getActiveWorkflowsByActiveGroups');

        $this->container->expects($this->never())
            ->method('get')
            ->with('oro_workflow.cache.entities_with_workflow');

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

        $this->entitiesWithWorkflowsCache->expects($this->once())
            ->method('deleteAll');

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

        $this->expectException(WorkflowActivationException::class);
        $this->expectExceptionMessage(
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
        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(PreUpdateEventArgs::class)->disableOriginalConstructor()->getMock();

        $event->expects($this->any())
            ->method('hasChangedField')
            ->willReturnMap([
                ['active', false],
                ['relatedEntity', true]
            ]);

        $workflow = $this->createWorkflow('workflow1', ['group1']);

        $this->entitiesWithWorkflowsCache->expects($this->once())->method('deleteAll');

        $this->listener->preUpdate($workflow->getDefinition(), $event);
    }

    public function testPreUpdateChangedIsActive()
    {
        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(PreUpdateEventArgs::class)->disableOriginalConstructor()->getMock();

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
            ->method('getActiveWorkflowsByActiveGroups')->with(['group1'])
            ->willReturn(new ArrayCollection([$workflow]));

        $this->entitiesWithWorkflowsCache->expects($this->once())->method('deleteAll');

        $this->listener->preUpdate($workflow->getDefinition(), $event);
    }

    public function testPreUpdateChangedNotTrackedProperty()
    {
        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(PreUpdateEventArgs::class)->disableOriginalConstructor()->getMock();

        $event->expects($this->any())
            ->method('hasChangedField')
            ->willReturnMap([
                ['active', false],
                ['relatedEntity', false]
            ]);

        $workflow = $this->createWorkflow('workflow1', ['group1']);

        $this->entitiesWithWorkflowsCache->expects($this->never())->method('deleteAll');

        $this->listener->preUpdate($workflow->getDefinition(), $event);
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

        $this->expectException(WorkflowActivationException::class);
        $this->expectExceptionMessage(
            'Workflow `workflow1` cannot be activated as it conflicts with' .
            ' workflow `conflict_workflow` by exclusive_active_group `group1`.'
        );

        $this->entitiesWithWorkflowsCache->expects($this->never())->method('deleteAll');

        $this->listener->preUpdate($workflow->getDefinition(), $eventMock);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowRemoveException
     * @expectedExceptionMessage Workflow 'workflow1' can't be removed due its System workflow
     */
    public function testPreRemoveSystemWorkflowException()
    {
        /** @var WorkflowDefinition|\PHPUnit\Framework\MockObject\MockObject $definitionMock */
        $definitionMock = $this->createMock(WorkflowDefinition::class);
        $definitionMock->expects($this->once())->method('isSystem')->willReturn(true);
        $definitionMock->expects($this->once())->method('getName')->willReturn('workflow1');

        $this->listener->preRemove($definitionMock);
    }

    public function testPreRemoveSystemWorkflow()
    {
        /** @var WorkflowDefinition|\PHPUnit\Framework\MockObject\MockObject $definitionMock */
        $definitionMock = $this->createMock(WorkflowDefinition::class);
        $definitionMock->expects($this->once())->method('isSystem')->willReturn(false);
        $definitionMock->expects($this->never())->method('getName');

        $this->entitiesWithWorkflowsCache->expects($this->once())->method('deleteAll');

        $this->listener->preRemove($definitionMock);
    }

    /**
     * @param string $name
     * @param array $groups
     * @return Workflow|\PHPUnit\Framework\MockObject\MockObject
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
     * @return WorkflowDefinition|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createDefinition($name, array $groups)
    {
        $definition = $this->getMockBuilder(WorkflowDefinition::class)->getMock();
        $definition->expects($this->any())->method('getName')->willReturn($name);
        $definition->expects($this->any())->method('getExclusiveActiveGroups')->willReturn($groups);

        return $definition;
    }
}
