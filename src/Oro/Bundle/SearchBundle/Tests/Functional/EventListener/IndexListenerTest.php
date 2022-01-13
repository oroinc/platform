<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByIdTopic;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @group search
 * @dbIsolationPerTest
 */
class IndexListenerTest extends WebTestCase
{
    use MessageQueueAssertTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();
    }

    public function testShouldCreateSearchIndexForEntityIfItWasCreated()
    {
        $em = $this->getDoctrine()->getManagerForClass(Item::class);
        self::getMessageCollector()->clear();

        // test
        $item = new Item();
        $em->persist($item);
        $em->flush();

        self::assertMessageSent(IndexEntitiesByIdTopic::getName(), [
            'class' => Item::class,
            'entityIds' => [$item->getId() => $item->getId()],
        ]);
    }

    public function testShouldUpdateSearchIndexForEntityIfItWasUpdated()
    {
        $em = $this->getDoctrine()->getManagerForClass(Item::class);

        $item = new Item();
        $em->persist($item);
        $em->flush();
        self::getMessageCollector()->clear();

        // test
        $item->stringValue = 'value';
        $em->flush();

        self::assertMessageSent(IndexEntitiesByIdTopic::getName(), [
            'class' => Item::class,
            'entityIds' => [$item->getId() => $item->getId()],
        ]);
    }

    public function testShouldDeleteSearchIndexForEntityIfItWasDeleted()
    {
        $em = $this->getDoctrine()->getManagerForClass(Item::class);

        $item = new Item();
        $em->persist($item);
        $em->flush();

        self::getMessageCollector()->clear();

        $itemId = $item->getId();
        $this->assertNotNull($itemId);

        // test
        $em->remove($item);
        $em->flush();

        self::assertMessageSent(IndexEntitiesByIdTopic::getName(), [
            'class' => Item::class,
            'entityIds' => [$itemId => $itemId],
        ]);
    }

    /**
     * @return ManagerRegistry
     */
    private function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }
}
