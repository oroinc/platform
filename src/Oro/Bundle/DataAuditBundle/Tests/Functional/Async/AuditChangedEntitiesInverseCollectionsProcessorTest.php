<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesInverseCollectionsChunkProcessor;
use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesInverseCollectionsProcessor;
use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Bundle\DataAuditBundle\Tests\Functional\DataFixtures\LoadTestAuditDataWithOneToManyData;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Stub\AuditField;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\Message as ClientMessage;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Util\JSON;

class AuditChangedEntitiesInverseCollectionsProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use MessageQueueAssertTrait;
    use AuditChangedEntitiesExtensionTrait;

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
        $this->assertMessagesEmpty(Topics::ENTITIES_INVERSED_RELATIONS_CHANGED_COLLECTIONS);

        $this->chunkProcessor->process($message, $this->getConnection()->createSession());
        $this->assertMessagesEmpty(Topics::ENTITIES_INVERSED_RELATIONS_CHANGED_COLLECTIONS_CHUNK);
    }

    public function testProcessorSplitCollectionToChunkAndSaveAudit(): void
    {
        $batchSize = 2;
        /** @var TestAuditDataOwner $testAuditOwner */
        $testAuditOwner = $this->getReference('testAuditOwner');
        /** @var ConnectionInterface $connection */
        $connection = $this->getConnection();
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
        ]);

        $this->processor->setBatchSize($batchSize);
        $this->processor->process($message, $connection->createSession());

        $this->assertMessagesCount(Topics::ENTITIES_INVERSED_RELATIONS_CHANGED_COLLECTIONS_CHUNK, $expectedCount);
        $this->assertMessagesCreatedAndEntityIdsIsSplitting($batchSize);

        $this->processedMessages();

        $this->assertStoredAuditCount(4);
        $this->assertStoredAuditHasOwnerChanged();
    }

    private function processedMessages(): void
    {
        $connection = $this->getConnection();
        $session = $connection->createSession();
        foreach (self::getSentMessages() as $sentMessage) {
            /** @var ClientMessage $message */
            $message = $sentMessage['message'];
            $this->chunkProcessor->process($session->createMessage(JSON::encode($message->getBody())), $session);
        }
    }

    private function assertMessagesCreatedAndEntityIdsIsSplitting(int $batchSize): void
    {
        foreach (self::getSentMessages() as $sentMessage) {
            /** @var ClientMessage $sentMessage */
            $topic = $sentMessage['topic'];
            $message = $sentMessage['message'];
            $body = $message->getBody();
            $countIds = count($body['entityData']['fields']['childrenOneToMany']['entity_ids']);

            $this->assertEquals($batchSize, $countIds);
            $this->assertEquals(Topics::ENTITIES_INVERSED_RELATIONS_CHANGED_COLLECTIONS_CHUNK, $topic);
        }
    }

    private function assertStoredAuditHasOwnerChanged(): void
    {
        foreach ($this->findStoredAudits() as $audit) {
            $fields = $audit->getFields();
            $this->assertCount(1, $fields);

            /** @var AuditField $field */
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

    /**
     * @param array $body
     *
     * @return Message
     */
    private function createMessage(array $body): MessageInterface
    {
        $message = new Message();
        $message->setBody(JSON::encode($body));
        $message->setMessageId('some_message_id');

        return $message;
    }

    /**
     * @return object|EntityManagerInterface
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return ConnectionInterface|object
     */
    private function getConnection(): ConnectionInterface
    {
        return $this->getContainer()->get('oro_message_queue.transport.connection');
    }
}
