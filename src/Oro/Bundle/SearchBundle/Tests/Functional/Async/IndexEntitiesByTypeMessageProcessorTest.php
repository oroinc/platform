<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SearchBundle\Async\IndexEntitiesByTypeMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByRangeTopic;
use Oro\Bundle\SearchBundle\Entity\Item as IndexItem;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @nestTransactionsWithSavepoints
 * @group search
 */
class IndexEntitiesByTypeMessageProcessorTest extends WebTestCase
{
    use SearchExtensionTrait;
    use MessageQueueExtension;

    protected function setUp(): void
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
        $message = new Message();
        $message->setBody([
            'entityClass' => Item::class,
            'jobId' => $childJob->getId(),
        ]);

        $this->getSearchIndexer()->resetIndex(Item::class);
        self::getMessageCollector()->clear();

        $this->getIndexEntitiesByTypeMessageProcessor()
            ->process($message, $this->createMock(SessionInterface::class));

        $messages = self::getSentMessagesByTopic(IndexEntitiesByRangeTopic::getName());

        $this->assertCount(1, $messages);

        $this->assertEquals(Item::class, $messages[0]['entityClass']);
        $this->assertEquals(0, $messages[0]['offset']);
        $this->assertEquals(1000, $messages[0]['limit']);
        $this->assertIsInt($messages[0]['jobId']);
    }

    private function getJobProcessor(): JobProcessor
    {
        return self::getContainer()->get('oro_message_queue.job.processor');
    }

    private function getDoctrine(): ManagerRegistry
    {
        return self::getContainer()->get('doctrine');
    }

    private function getIndexEntitiesByTypeMessageProcessor(): IndexEntitiesByTypeMessageProcessor
    {
        return self::getContainer()->get('oro_search.async.index_entities_by_type_processor');
    }
}
