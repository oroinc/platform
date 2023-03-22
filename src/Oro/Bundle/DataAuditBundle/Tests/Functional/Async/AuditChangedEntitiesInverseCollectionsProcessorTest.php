<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Async;

use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesInverseCollectionsChunkProcessor;
use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesInverseCollectionsProcessor;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsChunkTopic;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsTopic;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Exception\WrongDataAuditEntryStateException;
use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Bundle\DataAuditBundle\Tests\Functional\DataFixtures\LoadTestAuditDataWithOneToManyData;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\Config as MessageQueueConfig;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Psr\Log\NullLogger;

class AuditChangedEntitiesInverseCollectionsProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use MessageQueueAssertTrait;
    use AuditChangedEntitiesExtensionTrait;
    use JobsAwareTestTrait;

    /** @var AuditChangedEntitiesInverseCollectionsProcessor */
    private $processor;

    /** @var AuditChangedEntitiesInverseCollectionsChunkProcessor */
    private $chunkProcessor;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->setUpMessageCollector();
        $this->loadFixtures([LoadTestAuditDataWithOneToManyData::class]);
        $this->processor = $this->getContainer()
            ->get('oro_dataaudit.async.audit_changed_entities_inverse_collections');
        $this->chunkProcessor = $this->getContainer()
            ->get('oro_dataaudit.async.audit_changed_entities_inverse_collections_chunk');
    }

    public function testChunkProcessorProcessResult()
    {
        $testAuditOwner = $this->getReference('testAuditOwner');
        $session = $this->getConnection()->createSession();
        $job = $this->createUniqueJob();
        $message = $this->createMessage([
            'jobId' => $job->getId(),
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entityData' => [
                'entity_class' => TestAuditDataOwner::class,
                'entity_id' => $testAuditOwner->getId(),
                'change_set' => ['stringProperty' => [null, 'aNewValue']],
                'set' => 'updated',
                'fields' => [],
            ]
        ]);

        $converter = $this->createMock(EntityChangesToAuditEntryConverter::class);
        $logger = $this->createMock(NullLogger::class);
        $chunkProcessor = new AuditChangedEntitiesInverseCollectionsChunkProcessor(
            $converter,
            $this->getJobRunner()
        );
        $chunkProcessor->setLogger($logger);

        $logger->expects($this->never())->method('log');
        $processResult = $chunkProcessor->process($message, $session);
        $this->assertEquals(AuditChangedEntitiesInverseCollectionsChunkProcessor::ACK, $processResult);

        $logger->expects($this->once())->method('warning');
        $exception = new WrongDataAuditEntryStateException((new Audit())->setObjectName('ObjectName'));
        $converter->method('convert')->willThrowException($exception);

        $processResult = $chunkProcessor->process($message, $session);
        $this->assertEquals(AuditChangedEntitiesInverseCollectionsChunkProcessor::REQUEUE, $processResult);

        $logger->expects($this->once())->method('error');
        $converter->method('convert')->willThrowException(new \Exception('exception'));
        $processResult = $chunkProcessor->process($message, $session);
        $this->assertEquals(AuditChangedEntitiesInverseCollectionsChunkProcessor::REJECT, $processResult);
    }

    public function testCouldBeGetInverseCollectionFromContainerAsService(): void
    {
        $this->assertInstanceOf(AuditChangedEntitiesInverseCollectionsProcessor::class, $this->processor);
    }

    public function testCouldBeGetInverseCollectionsChunkFromContainerAsService(): void
    {
        $this->assertInstanceOf(AuditChangedEntitiesInverseCollectionsChunkProcessor::class, $this->chunkProcessor);
    }

    public function testProcessingWithEmptyMessage(): void
    {
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());
        $this->assertMessagesEmpty(AuditChangedEntitiesInverseCollectionsTopic::getName());
    }

    public function testProcessorSplitCollectionToChunkAndSaveAudit(): void
    {
        $batchSize = 2;
        /** @var TestAuditDataOwner $testAuditOwner */
        $testAuditOwner = $this->getReference('testAuditOwner');
        $expectedCount = ceil($testAuditOwner->getChildrenOneToMany()->count() / $batchSize);
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entities_inserted' => [],
            'entities_updated' => [
                '000000001f4c2232000000006d016312' => [
                    'entity_class' => TestAuditDataOwner::class,
                    'entity_id' => $testAuditOwner->getId(),
                    'change_set' => ['stringProperty' => [null, 'aNewValue']],
                ]
            ],
            'entities_deleted' => [],
            'collections_updated' => [],
        ], [
            MessageQueueConfig::PARAMETER_TOPIC_NAME => AuditChangedEntitiesInverseCollectionsTopic::getName(),
            JobAwareTopicInterface::UNIQUE_JOB_NAME => 'fakeJobID'
        ]);

        $this->createRootJobMyMessage($message);

        $this->processor->setBatchSize($batchSize);
        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertMessagesCount(AuditChangedEntitiesInverseCollectionsChunkTopic::getName(), $expectedCount);
        $this->assertMessagesCreatedAndEntityIdsIsSplitting($batchSize);

        $this->processedMessages();

        $this->assertStoredAuditCount(4);
        $this->assertStoredAuditHasOwnerChanged();
    }

    private function processedMessages(): void
    {
        $session = $this->getConnection()->createSession();
        foreach (self::getSentMessages() as $sentMessage) {
            $this->chunkProcessor->process($session->createMessage($sentMessage['message']), $session);
        }
    }

    private function assertMessagesCreatedAndEntityIdsIsSplitting(int $batchSize): void
    {
        foreach (self::getSentMessages() as $sentMessage) {
            $topic = $sentMessage['topic'];
            $messageBody = $sentMessage['message'];

            $this->assertCount($batchSize, $messageBody['entityData']['fields']['childrenOneToMany']['entity_ids']);
            $this->assertEquals(AuditChangedEntitiesInverseCollectionsChunkTopic::getName(), $topic);
        }
    }

    private function assertStoredAuditHasOwnerChanged(): void
    {
        foreach ($this->findStoredAudits() as $audit) {
            $fields = $audit->getFields();
            $this->assertCount(1, $fields);

            $field = $audit->getField('ownerManyToOne');
            $changedDiffs = $field->getCollectionDiffs()['changed'];
            $this->assertCount(1, $changedDiffs);
            $diff = reset($changedDiffs);

            $this->assertEquals(TestAuditDataOwner::class, $diff['entity_class']);
            $this->assertEquals(
                ['stringProperty' => [null,'aNewValue']],
                $diff['change_set']
            );
        }
    }

    private function createMessage(array $body, array $properties = []): MessageInterface
    {
        $message = new Message();
        $message->setBody($body);
        $message->setMessageId('some_message_id');

        $message->setProperties($properties);

        return $message;
    }

    private function getConnection(): ConnectionInterface
    {
        return self::getContainer()->get('oro_message_queue.transport.connection');
    }
}
