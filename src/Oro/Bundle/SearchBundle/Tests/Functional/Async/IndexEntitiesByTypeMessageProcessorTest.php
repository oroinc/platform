<?php
namespace Oro\Bundle\SearchBundle\Tests\Functional\Async;

use Oro\Bundle\SearchBundle\Async\IndexEntitiesByTypeMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\SearchExtensionTrait;
use Oro\Bundle\SearchBundle\Entity\Item as IndexItem;

class IndexEntitiesByTypeMessageProcessorTest extends WebTestCase
{
    use SearchExtensionTrait;

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

        // guard
        $itemIndex = $indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]);
        $this->assertEmpty($itemIndex);

        // test
        $message = new NullMessage();
        $message->setBody(Item::class);

        $this->getSearchIndexer()->resetIndex(Item::class);
        $this->getMessageProducer()->enable();
        $this->getMessageProducer()->clear();

        $this->getIndexEntitiesByTypeMessageProcessor()->process($message, $this->createQueueSessionMock());

        $messages = $this->getMessageProducer()->getSentMessages();

        $expectedMessage = [
            'class' => Item::class,
            'offset' => 0,
            'limit' => 1000,
        ];

        $this->assertCount(1, $messages);
        $this->assertEquals(Topics::INDEX_ENTITY_BY_RANGE, $messages[0]['topic']);
        $this->assertEquals($expectedMessage, $messages[0]['message']);
    }

    /**
     * @return \Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector
     */
    protected function getMessageProducer()
    {
        return $this->getContainer()->get('oro_message_queue.client.message_producer');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    protected function createQueueSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     */
    protected function getDoctrine()
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
