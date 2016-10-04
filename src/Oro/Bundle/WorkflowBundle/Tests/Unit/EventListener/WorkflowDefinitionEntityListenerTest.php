<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionEntityListener;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowActivationException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowDefinitionEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowDefinitionEntityListener */
    protected $listener;

    /** @var TranslationProcessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $translationProcessor;

    /** @var WorkflowRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowRegistry;

    protected function setUp()
    {
        $this->workflowRegistry = $this->getMockBuilder(WorkflowRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translationProcessor = $this->getMockBuilder(TranslationProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WorkflowDefinitionEntityListener($this->workflowRegistry, $this->translationProcessor);
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
        $this->workflowRegistry->expects($this->once())->method('getActiveWorkflowsByActiveGroups')->willReturn([]);

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

        $this->workflowRegistry->expects($this->once())->method('getActiveWorkflowsByActiveGroups')->willReturn([
            $workflowMock
        ]);

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

        $this->workflowRegistry->expects($this->once())->method('getActiveWorkflowsByActiveGroups')->willReturn([
            $this->createWorkflow('conflict_workflow1', ['group1']),
            $this->createWorkflow('conflict_workflow2', ['group2'])
        ]);

        $this->setExpectedException(
            WorkflowActivationException::class,
            'Workflow `workflow1` cannot be activated as it conflicts with' .
            ' workflow `conflict_workflow1` by exclusive_active_group `group1`,' .
            ' workflow `conflict_workflow2` by exclusive_active_group `group2`.'
        );

        $this->listener->prePersist($definitionMock);
    }

    public function testPrePersistAndFlush()
    {
        $definition = $this->createDefinition('test', []);
        $event = $this->getMockBuilder(PostFlushEventArgs::class)->disableOriginalConstructor()->getMock();

        $this->translationProcessor->expects($this->once())
            ->method('process')
            ->with($definition, null, false);

        $this->listener->prePersist($definition);
        $this->listener->postFlush($event);
    }

    public function testPreUpdateWithoutChangesWithoutChanges()
    {
        $updateEvent = $this->getMockBuilder(PreUpdateEventArgs::class)->disableOriginalConstructor()->getMock();
        $updateEvent->expects($this->once())->method('hasChangedField')->with('active')->willReturn(false);
        $updateEvent->expects($this->once())->method('getEntityChangeSet')->willReturn([]);

        $flushEvent = $this->getMockBuilder(PostFlushEventArgs::class)->disableOriginalConstructor()->getMock();

        $this->translationProcessor->expects($this->never())->method($this->anything());

        $this->listener->preUpdate($this->createDefinition('test', []), $updateEvent);
        $this->listener->postFlush($flushEvent);
    }

    /**
     * @dataProvider preUpdateDataProvider
     *
     * @param array $changeSet
     * @param array $expectedChangeSet
     */
    public function testPreUpdateAndFlush(array $changeSet, array $expectedChangeSet)
    {
        $updateEvent = $this->getMockBuilder(PreUpdateEventArgs::class)->disableOriginalConstructor()->getMock();
        $updateEvent->expects($this->once())->method('hasChangedField')->with('active')->willReturn(false);
        $updateEvent->expects($this->once())->method('getEntityChangeSet')->willReturn($changeSet);

        $flushEvent = $this->getMockBuilder(PostFlushEventArgs::class)->disableOriginalConstructor()->getMock();

        $definition = $this->createDefinition('test', []);

        $this->translationProcessor->expects($this->once())
            ->method('process')
            ->with($definition, $expectedChangeSet, false);

        $this->listener->preUpdate($definition, $updateEvent);
        $this->listener->postFlush($flushEvent);
    }

    /**
     * @return array
     */
    public function preUpdateDataProvider()
    {
        return [
            'field "name"' => [
                'changeSet' => ['name' => ['old_name', 'new_name'], 'priority' => [0, 1]],
                'expectedChangeSet' => ['name' => ['old_name', 'new_name']]
            ],
            'field "label"' => [
                'changeSet' => ['label' => ['old_label', 'new_label'], 'priority' => [0, 1]],
                'expectedChangeSet' => ['label' => ['old_label', 'new_label']]
            ],
            'field "configuration"' => [
                'changeSet' => ['configuration' => [['old_data'], ['new_data']], 'priority' => [0, 1]],
                'expectedChangeSet' => ['configuration' => [['old_data'], ['new_data']]]
            ],
            'all fields' => [
                'changeSet' => [
                    'name' => ['old_name', 'new_name'],
                    'label' => ['oldlabel', 'new_label'],
                    'configuration' => [['old_data'], ['new_data']],
                    'priority' => [0, 1]
                ],
                'expectedChangeSet' => [
                    'name' => ['old_name', 'new_name'],
                    'label' => ['oldlabel', 'new_label'],
                    'configuration' => [['old_data'], ['new_data']]
                ]
            ],
        ];
    }

    public function testPreRemoveAndFlush()
    {
        $definition = $this->createDefinition('test', []);
        $event = $this->getMockBuilder(PostFlushEventArgs::class)->disableOriginalConstructor()->getMock();

        $this->translationProcessor->expects($this->once())
            ->method('process')
            ->with($definition, null, true);

        $this->listener->preRemove($definition);
        $this->listener->postFlush($event);
    }

    /**
     * @dataProvider onClearDataProvider
     *
     * @param bool $clearsAllEntities
     * @param string|null $entityClass
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expected
     */
    public function testOnClearAndFlush($clearsAllEntities, $entityClass, $expected)
    {
        $definition = $this->createDefinition('test', []);

        $clearEvent = $this->getMockBuilder(OnClearEventArgs::class)->disableOriginalConstructor()->getMock();
        $clearEvent->expects($this->any())->method('clearsAllEntities')->willReturn($clearsAllEntities);
        $clearEvent->expects($this->any())->method('getEntityClass')->willReturn($entityClass);

        $flushEvent = $this->getMockBuilder(PostFlushEventArgs::class)->disableOriginalConstructor()->getMock();

        $this->translationProcessor->expects($expected)->method('process');

        $this->listener->prePersist($definition);
        $this->listener->onClear($clearEvent);
        $this->listener->postFlush($flushEvent);
    }

    /**
     * @return array
     */
    public function onClearDataProvider()
    {
        return [
            'all entities' => [
                'clearsAllEntities' => true,
                'entityClass' => null,
                'expected' => $this->never()
            ],
            'workflow definition entity' => [
                'clearsAllEntities' => false,
                'entityClass' => WorkflowDefinition::class,
                'expected' => $this->never()
            ],
            'unknown entity' => [
                'clearsAllEntities' => false,
                'entityClass' => 'stdClass',
                'expected' => $this->once()
            ]
        ];
    }

    public function testPreUpdateConflictsException()
    {
        $eventMock = $this->getMockBuilder(PreUpdateEventArgs::class)->disableOriginalConstructor()->getMock();

        $eventMock->expects($this->once())->method('hasChangedField')->with('active')->willReturn(true);
        $eventMock->expects($this->once())->method('getNewValue')->with('active')->willReturn(true);

        $workflow = $this->createWorkflow('workflow1', ['group1']);

        $conflictingWorkflow = $this->createWorkflow('conflict_workflow', ['group1', 'group2']);

        $this->workflowRegistry->expects($this->once())->method('getActiveWorkflowsByActiveGroups')->with(
            ['group1']
        )->willReturn([$workflow, $conflictingWorkflow]);

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
