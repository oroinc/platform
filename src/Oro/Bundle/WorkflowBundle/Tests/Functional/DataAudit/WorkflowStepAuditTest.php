<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataAudit;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesProcessor;
use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAuditField;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\EventListener\SendWorkflowStepChangesToAuditListener;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowSteps;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\Testing\Assert\ArrayContainsConstraint;

/**
 * @dbIsolationPerTest
 */
class WorkflowStepAuditTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadWorkflowSteps::class]);
    }

    /**
     * @param array $messageBody
     */
    private function processMessage(array $messageBody)
    {
        $messageBody = array_replace(
            [
                'timestamp'           => (new \DateTime('2012-02-01 03:02:01+0000'))->getTimestamp(),
                'transaction_id'      => 'transactionId',
                'entities_inserted'   => [],
                'entities_updated'    => [],
                'entities_deleted'    => [],
                'collections_updated' => []
            ],
            $messageBody
        );

        $message = new NullMessage();
        $message->setBody(json_encode($messageBody));

        /** @var AuditChangedEntitiesProcessor $processor */
        $processor = self::getContainer()->get('oro_dataaudit.async.audit_changed_entities');
        $processor->process($message, new NullSession());
    }

    /**
     * @return TestAuditDataOwner
     */
    private function createTestAuditDataOwner()
    {
        $entity = new TestAuditDataOwner();
        $this->getReferenceRepository()->setReference('entity', $entity);
        $em = $this->getEntityManager(TestAuditDataOwner::class);
        $em->persist($entity);
        $em->flush();
        $this->clearMessageCollector();

        return $entity;
    }

    /**
     * @param string $entityClass
     *
     * @return EntityManagerInterface
     */
    private function getEntityManager($entityClass)
    {
        return $em = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass($entityClass);
    }

    /**
     * @return Audit
     */
    private function findLastStoredAudit()
    {
        $qb = $this->getEntityManager(Audit::class)
            ->createQueryBuilder()
            ->select('log')
            ->from(Audit::class, 'log')
            ->orderBy('log.id', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getSingleResult();
    }

    public function testProducerForStartWorkflow()
    {
        $entity = $this->createTestAuditDataOwner();
        $entityId = $entity->getId();
        /** @var WorkflowStep $startStep */
        $startStep = $this->getReference(LoadWorkflowSteps::STEP_2);
        $startStepId = $startStep->getId();

        $item = new WorkflowItem();
        $item->setDefinition($startStep->getDefinition());
        $item->setEntity($entity);
        $transition = new WorkflowTransitionRecord();
        $transition->setTransitionName('test');
        $transition->setWorkflowItem($item);
        $transition->setStepTo($startStep);
        $em = $this->getEntityManager(WorkflowItem::class);
        $em->persist($item);
        $em->persist($transition);
        $em->flush();

        $expectedChanges = [
            [
                'entity_class' => TestAuditDataOwner::class,
                'entity_id'    => $entityId,
                'change_set'   => [
                    SendWorkflowStepChangesToAuditListener::FIELD_ALIAS => [
                        null,
                        ['entity_class' => WorkflowStep::class, 'entity_id' => $startStepId]
                    ]
                ]
            ]
        ];
        $messageBody = self::getSentMessage(Topics::ENTITIES_CHANGED)->getBody();
        self::assertThat(
            $messageBody['entities_updated'],
            new ArrayContainsConstraint($expectedChanges)
        );
    }

    public function testProducerForStopWorkflow()
    {
        $entity = $this->createTestAuditDataOwner();
        $entityId = $entity->getId();
        /** @var WorkflowStep $lastStep */
        $lastStep = $this->getReference(LoadWorkflowSteps::STEP_2);
        $lastStepId = $lastStep->getId();

        $item = new WorkflowItem();
        $item->setDefinition($lastStep->getDefinition());
        $item->setEntity($entity);
        $transition = new WorkflowTransitionRecord();
        $transition->setTransitionName('test');
        $transition->setWorkflowItem($item);
        $transition->setStepFrom($lastStep);
        $em = $this->getEntityManager(WorkflowItem::class);
        $em->persist($item);
        $em->persist($transition);
        $em->flush();

        $expectedChanges = [
            [
                'entity_class' => TestAuditDataOwner::class,
                'entity_id'    => $entityId,
                'change_set'   => [
                    SendWorkflowStepChangesToAuditListener::FIELD_ALIAS => [
                        ['entity_class' => WorkflowStep::class, 'entity_id' => $lastStepId],
                        null
                    ]
                ]
            ]
        ];
        $messageBody = self::getSentMessage(Topics::ENTITIES_CHANGED)->getBody();
        self::assertThat(
            $messageBody['entities_updated'],
            new ArrayContainsConstraint($expectedChanges)
        );
    }

    public function testProducerForChangeWorkflowTransition()
    {
        $entity = $this->createTestAuditDataOwner();
        $entityId = $entity->getId();
        /** @var WorkflowStep $oldStep */
        $oldStep = $this->getReference(LoadWorkflowSteps::STEP_1);
        $oldStepId = $oldStep->getId();
        /** @var WorkflowStep $newStep */
        $newStep = $this->getReference(LoadWorkflowSteps::STEP_2);
        $newStepId = $newStep->getId();

        $item = new WorkflowItem();
        $item->setDefinition($newStep->getDefinition());
        $item->setEntity($entity);
        $transition = new WorkflowTransitionRecord();
        $transition->setTransitionName('test');
        $transition->setWorkflowItem($item);
        $transition->setStepFrom($oldStep);
        $transition->setStepTo($newStep);
        $em = $this->getEntityManager(WorkflowItem::class);
        $em->persist($item);
        $em->persist($transition);
        $em->flush();

        $expectedChanges = [
            [
                'entity_class' => TestAuditDataOwner::class,
                'entity_id'    => $entityId,
                'change_set'   => [
                    SendWorkflowStepChangesToAuditListener::FIELD_ALIAS => [
                        ['entity_class' => WorkflowStep::class, 'entity_id' => $oldStepId],
                        ['entity_class' => WorkflowStep::class, 'entity_id' => $newStepId]
                    ]
                ]
            ]
        ];
        $messageBody = self::getSentMessage(Topics::ENTITIES_CHANGED)->getBody();
        self::assertThat(
            $messageBody['entities_updated'],
            new ArrayContainsConstraint($expectedChanges)
        );
    }

    public function testConsumerForStartWorkflow()
    {
        /** @var WorkflowStep $startStep */
        $startStep = $this->getReference(LoadWorkflowSteps::STEP_2);

        $startStepId = $startStep->getId();
        $startStepLabel = $startStep->getLabel();
        $expectedWorkflowStepAuditFieldName = $startStep->getDefinition()->getLabel();

        $this->processMessage([
            'entities_updated' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id'    => 123,
                    'change_set'   => [
                        SendWorkflowStepChangesToAuditListener::FIELD_ALIAS => [
                            null,
                            ['entity_class' => WorkflowStep::class, 'entity_id' => $startStepId]
                        ]
                    ]
                ]
            ]
        ]);

        $audit = $this->findLastStoredAudit();
        $currentStepAuditField = $audit->getField($expectedWorkflowStepAuditFieldName);
        self::assertInstanceOf(AbstractAuditField::class, $currentStepAuditField);
        self::assertEquals('workflows', $currentStepAuditField->getTranslationDomain());
        self::assertEquals($expectedWorkflowStepAuditFieldName, $currentStepAuditField->getField());
        self::assertEquals('text', $currentStepAuditField->getDataType());
        self::assertNull($currentStepAuditField->getOldValue());
        self::assertEquals($startStepLabel, $currentStepAuditField->getNewValue());
    }

    public function testConsumerForStopWorkflow()
    {
        /** @var WorkflowStep $lastStep */
        $lastStep = $this->getReference(LoadWorkflowSteps::STEP_2);

        $lastStepId = $lastStep->getId();
        $lastStepLabel = $lastStep->getLabel();
        $expectedWorkflowStepAuditFieldName = $lastStep->getDefinition()->getLabel();

        $this->processMessage([
            'entities_updated' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id'    => 123,
                    'change_set'   => [
                        SendWorkflowStepChangesToAuditListener::FIELD_ALIAS => [
                            ['entity_class' => WorkflowStep::class, 'entity_id' => $lastStepId],
                            null
                        ]
                    ]
                ]
            ]
        ]);

        $audit = $this->findLastStoredAudit();
        $currentStepAuditField = $audit->getField($expectedWorkflowStepAuditFieldName);
        self::assertInstanceOf(AbstractAuditField::class, $currentStepAuditField);
        self::assertEquals('workflows', $currentStepAuditField->getTranslationDomain());
        self::assertEquals($expectedWorkflowStepAuditFieldName, $currentStepAuditField->getField());
        self::assertEquals('text', $currentStepAuditField->getDataType());
        self::assertEquals($lastStepLabel, $currentStepAuditField->getOldValue());
        self::assertNull($currentStepAuditField->getNewValue());
    }

    public function testConsumerForChangeWorkflowTransition()
    {
        /** @var WorkflowStep $oldStep */
        $oldStep = $this->getReference(LoadWorkflowSteps::STEP_1);
        /** @var WorkflowStep $newStep */
        $newStep = $this->getReference(LoadWorkflowSteps::STEP_2);

        $oldStepId = $oldStep->getId();
        $oldStepLabel = $oldStep->getLabel();
        $newStepId = $newStep->getId();
        $newStepLabel = $newStep->getLabel();
        $expectedWorkflowStepAuditFieldName = $newStep->getDefinition()->getLabel();

        $this->processMessage([
            'entities_updated' => [
                [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id'    => 123,
                    'change_set'   => [
                        SendWorkflowStepChangesToAuditListener::FIELD_ALIAS => [
                            ['entity_class' => WorkflowStep::class, 'entity_id' => $oldStepId],
                            ['entity_class' => WorkflowStep::class, 'entity_id' => $newStepId]
                        ]
                    ]
                ]
            ]
        ]);

        $audit = $this->findLastStoredAudit();
        $currentStepAuditField = $audit->getField($expectedWorkflowStepAuditFieldName);
        self::assertInstanceOf(AbstractAuditField::class, $currentStepAuditField);
        self::assertEquals('workflows', $currentStepAuditField->getTranslationDomain());
        self::assertEquals($expectedWorkflowStepAuditFieldName, $currentStepAuditField->getField());
        self::assertEquals('text', $currentStepAuditField->getDataType());
        self::assertEquals($oldStepLabel, $currentStepAuditField->getOldValue());
        self::assertEquals($newStepLabel, $currentStepAuditField->getNewValue());
    }
}
