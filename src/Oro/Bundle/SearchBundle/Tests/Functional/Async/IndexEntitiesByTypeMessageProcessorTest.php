<?php
namespace Oro\Bundle\SearchBundle\Tests\Functional\Async;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SearchBundle\Async\IndexEntitiesByTypeMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Bundle\SearchBundle\Entity\Item as IndexItem;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * @nestTransactionsWithSavepoints
 * @group search
 */
class IndexEntitiesByTypeMessageProcessorTest extends WebTestCase
{
    use SearchExtensionTrait;
    use MessageQueueExtension;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testShouldBreakOnBatchesAndSendMessageToProducer()
    {
        $itemManager = $this->getDoctrine()->getManagerForClass(Item::class);

        $indexManager = $this->getDoctrine()->getManagerForClass(IndexItem::class);
        $indexItemRepository = $indexManager->getRepository(IndexItem::class);

        $item = new Item();

        $itemManager->persist($item);
        $itemManager->flush();

        $rootJob = $this->getJobProcessor()->findOrCreateRootJob('ownerid', 'jobname');
        $childJob = $this->getJobProcessor()->findOrCreateChildJob('jobname', $rootJob);

        // guard
        $itemIndex = $indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]);
        $this->assertEmpty($itemIndex);

        // test
        $message = new NullMessage();
        $message->setBody(JSON::encode([
            'entityClass' => Item::class,
            'jobId' => $childJob->getId(),
        ]));

        $this->getSearchIndexer()->resetIndex(Item::class);
        self::getMessageCollector()->clear();

        $this->getIndexEntitiesByTypeMessageProcessor()->process($message, $this->createQueueSessionMock());

        $messages = self::getSentMessagesByTopic(Topics::INDEX_ENTITY_BY_RANGE);

        $this->assertCount(1, $messages);

        $this->assertEquals(Item::class, $messages[0]['entityClass']);
        $this->assertEquals(0, $messages[0]['offset']);
        $this->assertEquals(1000, $messages[0]['limit']);
        $this->assertInternalType('integer', $messages[0]['jobId']);
    }

    /**
     * @return \Oro\Component\MessageQueue\Job\JobProcessor
     */
    private function getJobProcessor()
    {
        return $this->getContainer()->get('oro_message_queue.job.processor');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createQueueSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * @return IndexEntitiesByTypeMessageProcessor
     */
    private function getIndexEntitiesByTypeMessageProcessor()
    {
        return $this->getContainer()->get('oro_search.async.message_processor.index_entities_by_type');
    }
}
