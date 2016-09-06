<?php
namespace Oro\Bundle\SearchBundle\Tests\Functional\Async;

use Oro\Bundle\SearchBundle\Async\IndexEntitiesByRangeMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\SearchBundle\Entity\Item as IndexItem;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @dbIsolationPerTest
 */
class IndexEntitiesByRangeMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_search.async.message_processor.index_entities_by_range');

        $this->assertInstanceOf(IndexEntitiesByRangeMessageProcessor::class, $instance);
    }

    public function testShouldCreateIndexForEntity()
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
        $message->setBody(json_encode([
            'class' => Item::class,
            'offset' => 0,
            'limit' => 1000,
        ]));

        $this->getIndexEntitiesByRangeMessageProcessor()->process($message, $this->createQueueSessionMock());

        $itemIndex = $indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]);
        $this->assertNotEmpty($itemIndex);
    }

    public function testShouldNotCreateIndexForEntityIfOutOfRange()
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
        $message->setBody(json_encode([
            'class' => Item::class,
            'offset' => 100000,
            'limit' => 1000,
        ]));

        $this->getIndexEntitiesByRangeMessageProcessor()->process($message, $this->createQueueSessionMock());

        $itemIndex = $indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]);
        $this->assertEmpty($itemIndex);
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
     * @return IndexEntitiesByRangeMessageProcessor
     */
    protected function getIndexEntitiesByRangeMessageProcessor()
    {
        return $this->getContainer()->get('oro_search.async.message_processor.index_entities_by_range');
    }
}
