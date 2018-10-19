<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Event\CollectAuditFieldsEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowStepRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\EventListener\CollectAuditFieldsListener;
use Oro\Bundle\WorkflowBundle\EventListener\SendWorkflowStepChangesToAuditListener;
use Oro\Bundle\WorkflowBundle\Exception\InvalidArgumentException;

class CollectAuditFieldsListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var CollectAuditFieldsListener
     */
    private $listener;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->listener = new CollectAuditFieldsListener($this->doctrineHelper);
    }

    public function testOnCollectAuditFieldsWhenNoDataInChanges()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');
        $event = new CollectAuditFieldsEvent('Class', [], []);

        $this->listener->onCollectAuditFields($event);
        $this->assertEmpty($event->getFields());
    }

    public function testOnCollectAuditFieldsWhenNoNewStepEntity()
    {
        $event = new CollectAuditFieldsEvent(
            'Class',
            [
                SendWorkflowStepChangesToAuditListener::FIELD_ALIAS => [
                    ['entity_id' => 1],
                    ['entity_id' => 2],
                ],
            ],
            []
        );
        $repository = $this->createMock(WorkflowStepRepository::class);
        $repository->expects($this->once())
            ->method('findByIds')
            ->with([1, 2])
            ->willReturn([]);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(WorkflowStep::class)
            ->willReturn($repository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('WorkflowStep was not found by identifier: 2');
        $this->listener->onCollectAuditFields($event);
    }

    public function testOnCollectAuditFieldsWhenNoOldStepEntity()
    {
        $event = new CollectAuditFieldsEvent(
            AuditField::class,
            [
                SendWorkflowStepChangesToAuditListener::FIELD_ALIAS => [
                    null,
                    ['entity_id' => 2],
                ],
            ],
            []
        );
        $definitionLabel = 'definition_label';
        $definition = new WorkflowDefinition();
        $definition->setLabel($definitionLabel);
        $newStepLabel = 'new_step_label';
        $newStepEntity = new WorkflowStep();
        $newStepEntity->setLabel($newStepLabel);
        $newStepEntity->setDefinition($definition);

        $repository = $this->createMock(WorkflowStepRepository::class);
        $repository->expects($this->once())
            ->method('findByIds')
            ->with([null, 2])
            ->willReturn([2 => $newStepEntity]);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(WorkflowStep::class)
            ->willReturn($repository);

        $this->listener->onCollectAuditFields($event);
        $expectedField = new AuditField(
            $definitionLabel,
            'string',
            $newStepLabel,
            null
        );
        $expectedField->setTranslationDomain('workflows');
        $this->assertEquals(
            [SendWorkflowStepChangesToAuditListener::FIELD_ALIAS => $expectedField],
            $event->getFields()
        );
    }

    public function testOnCollectAuditFields()
    {
        $event = new CollectAuditFieldsEvent(
            AuditField::class,
            [
                SendWorkflowStepChangesToAuditListener::FIELD_ALIAS => [
                    ['entity_id' => 1],
                    ['entity_id' => 2],
                ],
            ],
            []
        );
        $definitionLabel = 'definition_label';
        $definition = new WorkflowDefinition();
        $definition->setLabel($definitionLabel);
        $newStepLabel = 'new_step_label';
        $newStepEntity = new WorkflowStep();
        $newStepEntity->setLabel($newStepLabel);
        $newStepEntity->setDefinition($definition);
        $oldStepLabel = 'old_step_label';
        $oldStepEntity = new WorkflowStep();
        $oldStepEntity->setLabel($oldStepLabel);

        $repository = $this->createMock(WorkflowStepRepository::class);
        $repository->expects($this->once())
            ->method('findByIds')
            ->with([1, 2])
            ->willReturn([1 => $oldStepEntity, 2 => $newStepEntity]);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(WorkflowStep::class)
            ->willReturn($repository);

        $this->listener->onCollectAuditFields($event);
        $expectedField = new AuditField(
            $definitionLabel,
            'string',
            $newStepLabel,
            $oldStepLabel
        );
        $expectedField->setTranslationDomain('workflows');
        $this->assertEquals(
            [SendWorkflowStepChangesToAuditListener::FIELD_ALIAS => $expectedField],
            $event->getFields()
        );
    }
}
