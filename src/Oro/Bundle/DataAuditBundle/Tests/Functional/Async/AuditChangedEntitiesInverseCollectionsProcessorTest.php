<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Async;

use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesInverseCollectionsProcessor;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsChunkTopic;
use Oro\Bundle\DataAuditBundle\Tests\Functional\DataFixtures\LoadTestAuditDataWithOneToManyData;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueConsumerTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class AuditChangedEntitiesInverseCollectionsProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use MessageQueueAssertTrait;
    use MessageQueueConsumerTestTrait;
    use AuditChangedEntitiesExtensionTrait;

    private AuditChangedEntitiesInverseCollectionsProcessor $processor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->setUpMessageCollector();
        $this->loadFixtures([LoadTestAuditDataWithOneToManyData::class]);
        $this->processor = self::getContainer()
            ->get('oro_dataaudit.async.audit_changed_entities_inverse_collections');
    }

    private function getTestAuditDataOwner(string $reference): TestAuditDataOwner
    {
        return $this->getReference($reference);
    }

    private function getTestAuditDataChild(TestAuditDataOwner $owner, int $childIndex): TestAuditDataChild
    {
        return $owner->getChildrenOneToMany()->get($childIndex);
    }

    private function getEntityData(TestAuditDataOwner $owner, array $childIds): array
    {
        return [
            'entity_class' => TestAuditDataOwner::class,
            'entity_id' => $owner->getId(),
            'change_set' => ['stringProperty' => [null, 'aNewValue']],
            'set' => 'changed',
            'fields' => [
                'childrenOneToMany' => [
                    'field_name' => 'ownerManyToOne',
                    'entity_class' => TestAuditDataChild::class,
                    'entity_ids' => $childIds
                ]
            ]
        ];
    }

    private function createMessage(array $body, array $properties = []): MessageInterface
    {
        $message = new Message();
        $message->setBody($body);
        $message->setMessageId('some_message_id');
        $message->setProperties($properties);

        return $message;
    }

    private function assertStoredAuditHasOwnerChanged(): void
    {
        foreach ($this->findStoredAudits() as $audit) {
            $this->assertCount(1, $audit->getFields());

            $field = $audit->getField('ownerManyToOne');
            $changedDiffs = $field->getCollectionDiffs()['changed'];
            $this->assertCount(1, $changedDiffs);
            $diff = reset($changedDiffs);

            $this->assertEquals(TestAuditDataOwner::class, $diff['entity_class']);
            $this->assertEquals(['stringProperty' => [null, 'aNewValue']], $diff['change_set']);
        }
    }

    public function testProcessWhenNoChanges(): void
    {
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entities_inserted' => [],
            'entities_updated' => [],
            'entities_deleted' => [],
            'collections_updated' => []
        ]);

        $this->processor->process($message, $this->createMock(SessionInterface::class));
        self::assertMessagesEmpty(AuditChangedEntitiesInverseCollectionsChunkTopic::getName());
    }

    public function testProcess(): void
    {
        $batchSize = 3;
        $owner = $this->getTestAuditDataOwner('test_audit_owner_1');
        $child1 = $this->getTestAuditDataChild($owner, 0);
        $child2 = $this->getTestAuditDataChild($owner, 1);
        $child3 = $this->getTestAuditDataChild($owner, 2);
        $child4 = $this->getTestAuditDataChild($owner, 3);
        $timestamp = time();
        $message = $this->createMessage([
            'timestamp' => $timestamp,
            'transaction_id' => 'aTransactionId',
            'entities_inserted' => [],
            'entities_updated' => [
                '000000001f4c2232000000006d016312' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => $owner->getId(),
                    'change_set' => ['stringProperty' => [null, 'aNewValue']]
                ]
            ],
            'entities_deleted' => [],
            'collections_updated' => []
        ]);

        $this->processor->setBatchSize($batchSize);
        $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertMessagesSent(
            AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
            [
                [
                    'timestamp' => $timestamp,
                    'transaction_id' => 'aTransactionId',
                    'entityData' => [
                        $this->getEntityData($owner, [$child1->getId(), $child2->getId(), $child3->getId()])
                    ]
                ],
                [
                    'timestamp' => $timestamp,
                    'transaction_id' => 'aTransactionId',
                    'entityData' => [
                        $this->getEntityData($owner, [$child4->getId()])
                    ]
                ]
            ]
        );

        self::consume();
        $this->assertStoredAuditCount(4);
        $this->assertStoredAuditHasOwnerChanged();
    }
}
