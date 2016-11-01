<?php
namespace Oro\Bundle\SearchBundle\Tests\Functional\Async;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\SearchBundle\Async\IndexEntitiesByTypeMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\SearchBundle\Entity\Item as IndexItem;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Job\Topics as JobTopics;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * @dbIsolationPerTest
 * @group search
 */
class IndexEntitiesByTypeMessageProcessorTest extends WebTestCase
{
    use SearchExtensionTrait;
    use MessageQueueAssertTrait;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_search.async.message_processor.index_entities_by_type');

        $this->assertInstanceOf(IndexEntitiesByTypeMessageProcessor::class, $instance);
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
        self::getMessageCollector()->enable();
        self::getMessageCollector()->clear();

        $this->getIndexEntitiesByTypeMessageProcessor()->process($message, $this->createQueueSessionMock());

        $messages = self::getSentMessages();

        $this->assertCount(4, $messages);
        $this->assertEquals(JobTopics::CALCULATE_ROOT_JOB_STATUS, $messages[0]['topic']);
        $this->assertEquals(JobTopics::CALCULATE_ROOT_JOB_STATUS, $messages[1]['topic']);
        $this->assertEquals(JobTopics::CALCULATE_ROOT_JOB_STATUS, $messages[3]['topic']);

        $this->assertEquals(Topics::INDEX_ENTITY_BY_RANGE, $messages[2]['topic']);

        $this->assertEquals(Item::class, $messages[2]['message']['entityClass']);
        $this->assertEquals(0, $messages[2]['message']['offset']);
        $this->assertEquals(1000, $messages[2]['message']['limit']);
        $this->assertInternalType('integer', $messages[2]['message']['jobId']);
    }

    /**
     * @return \Oro\Component\MessageQueue\Job\JobProcessor
     */
    private function getJobProcessor()
    {
        return $this->getContainer()->get('oro_message_queue.job.processor');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createQueueSessionMock()
    {
        return $this->getMock(SessionInterface::class);
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
