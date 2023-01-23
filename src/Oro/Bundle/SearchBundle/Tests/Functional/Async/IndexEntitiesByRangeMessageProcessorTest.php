<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SearchBundle\Async\IndexEntitiesByRangeMessageProcessor;
use Oro\Bundle\SearchBundle\Entity\Item as IndexItem;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @nestTransactionsWithSavepoints
 * @group search
 */
class IndexEntitiesByRangeMessageProcessorTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testShouldCreateIndexForEntity()
    {
        $engine = self::getContainer()
            ->get('oro_search.engine.parameters')
            ->getEngineName();
        if ($engine !== 'orm') {
            $this->markTestIncomplete('BAP-12226: This test doesn\'t work with current search engine');
        }

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
            'offset' => 0,
            'limit' => 1000,
            'jobId' => $childJob->getId(),
        ]);

        $this->getIndexEntitiesByRangeMessageProcessor()
            ->process($message, $this->createMock(SessionInterface::class));

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

        $rootJob = $this->getJobProcessor()->findOrCreateRootJob('ownerid', 'jobname');
        $childJob = $this->getJobProcessor()->findOrCreateChildJob('jobname', $rootJob);

        // guard
        $itemIndex = $indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]);
        $this->assertEmpty($itemIndex);

        // test
        $message = new Message();
        $message->setBody([
            'class' => Item::class,
            'offset' => 100000,
            'limit' => 1000,
            'jobId' => $childJob->getId(),
        ]);

        $this->getIndexEntitiesByRangeMessageProcessor()
            ->process($message, $this->createMock(SessionInterface::class));

        $itemIndex = $indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]);
        $this->assertEmpty($itemIndex);
    }

    private function getJobProcessor(): JobProcessor
    {
        return self::getContainer()->get('oro_message_queue.job.processor');
    }

    private function getDoctrine(): ManagerRegistry
    {
        return self::getContainer()->get('doctrine');
    }

    private function getIndexEntitiesByRangeMessageProcessor(): IndexEntitiesByRangeMessageProcessor
    {
        return self::getContainer()->get('oro_search.async.index_entities_by_range_processor');
    }
}
