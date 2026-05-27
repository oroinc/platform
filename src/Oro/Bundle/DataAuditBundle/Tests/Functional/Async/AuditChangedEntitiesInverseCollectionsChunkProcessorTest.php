<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Async;

use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesInverseCollectionsChunkProcessor;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsChunkTopic;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Exception\WrongDataAuditEntryStateException;
use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Bundle\DataAuditBundle\Tests\Functional\DataFixtures\LoadTestAuditDataWithOneToManyData;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class AuditChangedEntitiesInverseCollectionsChunkProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->setUpMessageCollector();
        $this->loadFixtures([LoadTestAuditDataWithOneToManyData::class]);
    }

    private function createChunkProcessor(
        EntityChangesToAuditEntryConverter $converter,
        LoggerInterface $logger
    ): AuditChangedEntitiesInverseCollectionsChunkProcessor {
        $chunkProcessor = new AuditChangedEntitiesInverseCollectionsChunkProcessor(
            $converter,
            $this->createMock(JobRunner::class),
            self::getContainer()->get('oro_dataaudit.audit_config_provider')
        );
        $chunkProcessor->setProducer(self::getMessageProducer());
        $chunkProcessor->setLogger($logger);

        return $chunkProcessor;
    }

    private function getTestAuditDataOwner(string $reference): TestAuditDataOwner
    {
        return $this->getReference($reference);
    }

    private function getTestAuditDataChild(TestAuditDataOwner $owner): TestAuditDataChild
    {
        return $owner->getChildrenOneToMany()->first();
    }

    private function getEntityData(TestAuditDataOwner $owner, TestAuditDataChild $child): array
    {
        return [
            'entity_class' => TestAuditDataOwner::class,
            'entity_id' => $owner->getId(),
            'change_set' => ['stringProperty' => [null, 'aNewValue']],
            'set' => 'updated',
            'fields' => [
                'childrenOneToMany' => [
                    'field_name' => 'ownerManyToOne',
                    'entity_class' => TestAuditDataChild::class,
                    'entity_ids' => [$child->getId()]
                ]
            ]
        ];
    }

    private function getEntityChanges(TestAuditDataOwner $owner, TestAuditDataChild $child): array
    {
        return [
            TestAuditDataChild::class . $child->getId() => [
                'entity_class' => TestAuditDataChild::class,
                'entity_id' => $child->getId(),
                'change_set' => [
                    'ownerManyToOne' => [
                        ['deleted' => []],
                        [
                            'inserted' => [],
                            'changed' => [],
                            'updated' => [
                                TestAuditDataOwner::class . $owner->getId() => [
                                    'entity_class' => TestAuditDataOwner::class,
                                    'entity_id' => $owner->getId(),
                                    'change_set' => [
                                        'stringProperty' => [null, 'aNewValue']
                                    ]
                                ]
                            ]
                        ]
                    ]
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

    private function createWrongDataAuditEntryStateException(): WrongDataAuditEntryStateException
    {
        return new WrongDataAuditEntryStateException((new Audit())->setObjectName('ObjectName'));
    }

    public function testProcessOneEntity(): void
    {
        $owner = $this->getTestAuditDataOwner('test_audit_owner_1');
        $child = $this->getTestAuditDataChild($owner);
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entityData' => [$this->getEntityData($owner, $child)]
        ]);
        $entityChanges = $this->getEntityChanges($owner, $child);

        $converter = $this->createMock(EntityChangesToAuditEntryConverter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $chunkProcessor = $this->createChunkProcessor($converter, $logger);

        $converter->expects(self::once())
            ->method('convert')
            ->with($entityChanges);
        $logger->expects(self::never())
            ->method(self::anything());

        $processResult = $chunkProcessor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $processResult);

        self::assertMessagesEmpty(AuditChangedEntitiesInverseCollectionsChunkTopic::getName());
    }

    public function testProcessOneEntityInOldDataFormat(): void
    {
        $owner = $this->getTestAuditDataOwner('test_audit_owner_1');
        $child = $this->getTestAuditDataChild($owner);
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entityData' => $this->getEntityData($owner, $child)
        ]);
        $entityChanges = $this->getEntityChanges($owner, $child);

        $converter = $this->createMock(EntityChangesToAuditEntryConverter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $chunkProcessor = $this->createChunkProcessor($converter, $logger);

        $converter->expects(self::once())
            ->method('convert')
            ->with($entityChanges);
        $logger->expects(self::never())
            ->method(self::anything());

        $processResult = $chunkProcessor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $processResult);

        self::assertMessagesEmpty(AuditChangedEntitiesInverseCollectionsChunkTopic::getName());
    }

    public function testProcessSeveralEntities(): void
    {
        $owner1 = $this->getTestAuditDataOwner('test_audit_owner_1');
        $child1 = $this->getTestAuditDataChild($owner1);
        $owner2 = $this->getTestAuditDataOwner('test_audit_owner_2');
        $child2 = $this->getTestAuditDataChild($owner2);
        $owner3 = $this->getTestAuditDataOwner('test_audit_owner_3');
        $child3 = $this->getTestAuditDataChild($owner3);
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entityData' => [
                $this->getEntityData($owner1, $child1),
                $this->getEntityData($owner2, $child2),
                $this->getEntityData($owner3, $child3)
            ]
        ]);
        $entity1Changes = $this->getEntityChanges($owner1, $child1);
        $entity2Changes = $this->getEntityChanges($owner2, $child2);
        $entity3Changes = $this->getEntityChanges($owner3, $child3);

        $converter = $this->createMock(EntityChangesToAuditEntryConverter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $chunkProcessor = $this->createChunkProcessor($converter, $logger);

        $converter->expects(self::exactly(3))
            ->method('convert')
            ->withConsecutive(
                [$entity1Changes],
                [$entity2Changes],
                [$entity3Changes]
            );
        $logger->expects(self::never())
            ->method(self::anything());

        $processResult = $chunkProcessor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $processResult);

        self::assertMessagesEmpty(AuditChangedEntitiesInverseCollectionsChunkTopic::getName());
    }

    public function testProcessOneEntityWhenRequeueException(): void
    {
        $owner = $this->getTestAuditDataOwner('test_audit_owner_1');
        $child = $this->getTestAuditDataChild($owner);
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entityData' => [$this->getEntityData($owner, $child)]
        ]);

        $converter = $this->createMock(EntityChangesToAuditEntryConverter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $chunkProcessor = $this->createChunkProcessor($converter, $logger);

        $exception = $this->createWrongDataAuditEntryStateException();

        $converter->expects(self::once())
            ->method('convert')
            ->willThrowException($exception);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Unexpected retryable database exception occurred during Audit Changed Entities build.',
                [
                    'topic' => AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
                    'entityIndex' => 0,
                    'exception' => $exception
                ]
            );

        $processResult = $chunkProcessor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::REQUEUE, $processResult);

        self::assertMessagesEmpty(AuditChangedEntitiesInverseCollectionsChunkTopic::getName());
    }

    public function testProcessOneEntityWhenRequeueExceptionInOldDataFormat(): void
    {
        $owner = $this->getTestAuditDataOwner('test_audit_owner_1');
        $child = $this->getTestAuditDataChild($owner);
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entityData' => $this->getEntityData($owner, $child)
        ]);

        $converter = $this->createMock(EntityChangesToAuditEntryConverter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $chunkProcessor = $this->createChunkProcessor($converter, $logger);

        $exception = $this->createWrongDataAuditEntryStateException();

        $converter->expects(self::once())
            ->method('convert')
            ->willThrowException($exception);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Unexpected retryable database exception occurred during Audit Changed Entities build.',
                [
                    'topic' => AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
                    'exception' => $exception
                ]
            );

        $processResult = $chunkProcessor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::REQUEUE, $processResult);

        self::assertMessagesEmpty(AuditChangedEntitiesInverseCollectionsChunkTopic::getName());
    }

    public function testProcessSeveralEntitiesWhenRequeueExceptionForFirstEntity(): void
    {
        $owner1 = $this->getTestAuditDataOwner('test_audit_owner_1');
        $child1 = $this->getTestAuditDataChild($owner1);
        $owner2 = $this->getTestAuditDataOwner('test_audit_owner_2');
        $child2 = $this->getTestAuditDataChild($owner2);
        $owner3 = $this->getTestAuditDataOwner('test_audit_owner_3');
        $child3 = $this->getTestAuditDataChild($owner3);
        $timestamp = time();
        $message = $this->createMessage([
            'timestamp' => $timestamp,
            'transaction_id' => 'aTransactionId',
            'entityData' => [
                $this->getEntityData($owner1, $child1),
                $this->getEntityData($owner2, $child2),
                $this->getEntityData($owner3, $child3)
            ]
        ]);

        $converter = $this->createMock(EntityChangesToAuditEntryConverter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $chunkProcessor = $this->createChunkProcessor($converter, $logger);

        $exception = $this->createWrongDataAuditEntryStateException();

        $converter->expects(self::once())
            ->method('convert')
            ->willThrowException($exception);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Unexpected retryable database exception occurred during Audit Changed Entities build.',
                [
                    'topic' => AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
                    'entityIndex' => 0,
                    'exception' => $exception
                ]
            );

        $processResult = $chunkProcessor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $processResult);

        self::assertMessagesSent(
            AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
            [
                [
                    'timestamp' => $timestamp,
                    'transaction_id' => 'aTransactionId',
                    'entityData' => [
                        $this->getEntityData($owner1, $child1)
                    ]
                ],
                [
                    'timestamp' => $timestamp,
                    'transaction_id' => 'aTransactionId',
                    'entityData' => [
                        $this->getEntityData($owner2, $child2),
                        $this->getEntityData($owner3, $child3)
                    ]
                ]
            ]
        );
    }

    public function testProcessSeveralEntitiesWhenRequeueExceptionForSecondEntity(): void
    {
        $owner1 = $this->getTestAuditDataOwner('test_audit_owner_1');
        $child1 = $this->getTestAuditDataChild($owner1);
        $owner2 = $this->getTestAuditDataOwner('test_audit_owner_2');
        $child2 = $this->getTestAuditDataChild($owner2);
        $owner3 = $this->getTestAuditDataOwner('test_audit_owner_3');
        $child3 = $this->getTestAuditDataChild($owner3);
        $timestamp = time();
        $message = $this->createMessage([
            'timestamp' => $timestamp,
            'transaction_id' => 'aTransactionId',
            'entityData' => [
                $this->getEntityData($owner1, $child1),
                $this->getEntityData($owner2, $child2),
                $this->getEntityData($owner3, $child3)
            ]
        ]);
        $entity1Changes = $this->getEntityChanges($owner1, $child1);
        $entity2Changes = $this->getEntityChanges($owner2, $child2);

        $converter = $this->createMock(EntityChangesToAuditEntryConverter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $chunkProcessor = $this->createChunkProcessor($converter, $logger);

        $exception = $this->createWrongDataAuditEntryStateException();

        $converter->expects(self::exactly(2))
            ->method('convert')
            ->withConsecutive(
                [$entity1Changes],
                [$entity2Changes]
            )
            ->willReturnCallback(function (array $entityChanges) use ($exception, $entity2Changes) {
                if ($entityChanges === $entity2Changes) {
                    throw $exception;
                }
            });
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Unexpected retryable database exception occurred during Audit Changed Entities build.',
                [
                    'topic' => AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
                    'entityIndex' => 1,
                    'exception' => $exception
                ]
            );

        $processResult = $chunkProcessor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $processResult);

        self::assertMessagesSent(
            AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
            [
                [
                    'timestamp' => $timestamp,
                    'transaction_id' => 'aTransactionId',
                    'entityData' => [
                        $this->getEntityData($owner2, $child2)
                    ]
                ],
                [
                    'timestamp' => $timestamp,
                    'transaction_id' => 'aTransactionId',
                    'entityData' => [
                        $this->getEntityData($owner3, $child3)
                    ]
                ]
            ]
        );
    }

    public function testProcessSeveralEntitiesWhenRequeueExceptionForLastEntity(): void
    {
        $owner1 = $this->getTestAuditDataOwner('test_audit_owner_1');
        $child1 = $this->getTestAuditDataChild($owner1);
        $owner2 = $this->getTestAuditDataOwner('test_audit_owner_2');
        $child2 = $this->getTestAuditDataChild($owner2);
        $owner3 = $this->getTestAuditDataOwner('test_audit_owner_3');
        $child3 = $this->getTestAuditDataChild($owner3);
        $timestamp = time();
        $message = $this->createMessage([
            'timestamp' => $timestamp,
            'transaction_id' => 'aTransactionId',
            'entityData' => [
                $this->getEntityData($owner1, $child1),
                $this->getEntityData($owner2, $child2),
                $this->getEntityData($owner3, $child3)
            ]
        ]);
        $entity1Changes = $this->getEntityChanges($owner1, $child1);
        $entity2Changes = $this->getEntityChanges($owner2, $child2);
        $entity3Changes = $this->getEntityChanges($owner3, $child3);

        $converter = $this->createMock(EntityChangesToAuditEntryConverter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $chunkProcessor = $this->createChunkProcessor($converter, $logger);

        $exception = $this->createWrongDataAuditEntryStateException();

        $converter->expects(self::exactly(3))
            ->method('convert')
            ->withConsecutive(
                [$entity1Changes],
                [$entity2Changes],
                [$entity3Changes]
            )
            ->willReturnCallback(function (array $entityChanges) use ($exception, $entity3Changes) {
                if ($entityChanges === $entity3Changes) {
                    throw $exception;
                }
            });
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Unexpected retryable database exception occurred during Audit Changed Entities build.',
                [
                    'topic' => AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
                    'entityIndex' => 2,
                    'exception' => $exception
                ]
            );

        $processResult = $chunkProcessor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $processResult);

        self::assertMessagesSent(
            AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
            [
                [
                    'timestamp' => $timestamp,
                    'transaction_id' => 'aTransactionId',
                    'entityData' => [
                        $this->getEntityData($owner3, $child3)
                    ]
                ]
            ]
        );
    }

    public function testProcessOneEntityWhenException(): void
    {
        $owner = $this->getTestAuditDataOwner('test_audit_owner_1');
        $child = $this->getTestAuditDataChild($owner);
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entityData' => [$this->getEntityData($owner, $child)]
        ]);

        $exception = new \Exception('an error');

        $converter = $this->createMock(EntityChangesToAuditEntryConverter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $chunkProcessor = $this->createChunkProcessor($converter, $logger);

        $converter->expects(self::once())
            ->method('convert')
            ->willThrowException($exception);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Audit Changed Entities build.',
                [
                    'topic' => AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
                    'entityIndex' => 0,
                    'exception' => $exception
                ]
            );

        $processResult = $chunkProcessor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::REJECT, $processResult);

        self::assertMessagesEmpty(AuditChangedEntitiesInverseCollectionsChunkTopic::getName());
    }

    public function testProcessOneEntityWhenExceptionInOldDataFormat(): void
    {
        $owner = $this->getTestAuditDataOwner('test_audit_owner_1');
        $child = $this->getTestAuditDataChild($owner);
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entityData' => $this->getEntityData($owner, $child)
        ]);

        $exception = new \Exception('an error');

        $converter = $this->createMock(EntityChangesToAuditEntryConverter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $chunkProcessor = $this->createChunkProcessor($converter, $logger);

        $converter->expects(self::once())
            ->method('convert')
            ->willThrowException($exception);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Audit Changed Entities build.',
                [
                    'topic' => AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
                    'exception' => $exception
                ]
            );

        $processResult = $chunkProcessor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::REJECT, $processResult);

        self::assertMessagesEmpty(AuditChangedEntitiesInverseCollectionsChunkTopic::getName());
    }

    public function testProcessSeveralEntitiesWhenExceptionForFirstEntity(): void
    {
        $owner1 = $this->getTestAuditDataOwner('test_audit_owner_1');
        $child1 = $this->getTestAuditDataChild($owner1);
        $owner2 = $this->getTestAuditDataOwner('test_audit_owner_2');
        $child2 = $this->getTestAuditDataChild($owner2);
        $owner3 = $this->getTestAuditDataOwner('test_audit_owner_3');
        $child3 = $this->getTestAuditDataChild($owner3);
        $timestamp = time();
        $message = $this->createMessage([
            'timestamp' => $timestamp,
            'transaction_id' => 'aTransactionId',
            'entityData' => [
                $this->getEntityData($owner1, $child1),
                $this->getEntityData($owner2, $child2),
                $this->getEntityData($owner3, $child3)
            ]
        ]);

        $converter = $this->createMock(EntityChangesToAuditEntryConverter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $chunkProcessor = $this->createChunkProcessor($converter, $logger);

        $exception = new \Exception('an error');

        $converter->expects(self::once())
            ->method('convert')
            ->willThrowException($exception);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Audit Changed Entities build.',
                [
                    'topic' => AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
                    'entityIndex' => 0,
                    'exception' => $exception
                ]
            );

        $processResult = $chunkProcessor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $processResult);

        self::assertMessagesSent(
            AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
            [
                [
                    'timestamp' => $timestamp,
                    'transaction_id' => 'aTransactionId',
                    'entityData' => [
                        $this->getEntityData($owner2, $child2),
                        $this->getEntityData($owner3, $child3)
                    ]
                ]
            ]
        );
    }

    public function testProcessSeveralEntitiesWhenExceptionForSecondEntity(): void
    {
        $owner1 = $this->getTestAuditDataOwner('test_audit_owner_1');
        $child1 = $this->getTestAuditDataChild($owner1);
        $owner2 = $this->getTestAuditDataOwner('test_audit_owner_2');
        $child2 = $this->getTestAuditDataChild($owner2);
        $owner3 = $this->getTestAuditDataOwner('test_audit_owner_3');
        $child3 = $this->getTestAuditDataChild($owner3);
        $timestamp = time();
        $message = $this->createMessage([
            'timestamp' => $timestamp,
            'transaction_id' => 'aTransactionId',
            'entityData' => [
                $this->getEntityData($owner1, $child1),
                $this->getEntityData($owner2, $child2),
                $this->getEntityData($owner3, $child3)
            ]
        ]);
        $entity1Changes = $this->getEntityChanges($owner1, $child1);
        $entity2Changes = $this->getEntityChanges($owner2, $child2);

        $converter = $this->createMock(EntityChangesToAuditEntryConverter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $chunkProcessor = $this->createChunkProcessor($converter, $logger);

        $exception = new \Exception('an error');

        $converter->expects(self::exactly(2))
            ->method('convert')
            ->withConsecutive(
                [$entity1Changes],
                [$entity2Changes]
            )
            ->willReturnCallback(function (array $entityChanges) use ($exception, $entity2Changes) {
                if ($entityChanges === $entity2Changes) {
                    throw $exception;
                }
            });
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Audit Changed Entities build.',
                [
                    'topic' => AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
                    'entityIndex' => 1,
                    'exception' => $exception
                ]
            );

        $processResult = $chunkProcessor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $processResult);

        self::assertMessagesSent(
            AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
            [
                [
                    'timestamp' => $timestamp,
                    'transaction_id' => 'aTransactionId',
                    'entityData' => [
                        $this->getEntityData($owner3, $child3)
                    ]
                ]
            ]
        );
    }

    public function testProcessSeveralEntitiesWhenExceptionForLastEntity(): void
    {
        $owner1 = $this->getTestAuditDataOwner('test_audit_owner_1');
        $child1 = $this->getTestAuditDataChild($owner1);
        $owner2 = $this->getTestAuditDataOwner('test_audit_owner_2');
        $child2 = $this->getTestAuditDataChild($owner2);
        $owner3 = $this->getTestAuditDataOwner('test_audit_owner_3');
        $child3 = $this->getTestAuditDataChild($owner3);
        $timestamp = time();
        $message = $this->createMessage([
            'timestamp' => $timestamp,
            'transaction_id' => 'aTransactionId',
            'entityData' => [
                $this->getEntityData($owner1, $child1),
                $this->getEntityData($owner2, $child2),
                $this->getEntityData($owner3, $child3)
            ]
        ]);
        $entity1Changes = $this->getEntityChanges($owner1, $child1);
        $entity2Changes = $this->getEntityChanges($owner2, $child2);
        $entity3Changes = $this->getEntityChanges($owner3, $child3);

        $converter = $this->createMock(EntityChangesToAuditEntryConverter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $chunkProcessor = $this->createChunkProcessor($converter, $logger);

        $exception = new \Exception('an error');

        $converter->expects(self::exactly(3))
            ->method('convert')
            ->withConsecutive(
                [$entity1Changes],
                [$entity2Changes],
                [$entity3Changes]
            )
            ->willReturnCallback(function (array $entityChanges) use ($exception, $entity3Changes) {
                if ($entityChanges === $entity3Changes) {
                    throw $exception;
                }
            });
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Audit Changed Entities build.',
                [
                    'topic' => AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
                    'entityIndex' => 2,
                    'exception' => $exception
                ]
            );

        $processResult = $chunkProcessor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $processResult);

        self::assertMessagesEmpty(AuditChangedEntitiesInverseCollectionsChunkTopic::getName());
    }
}
