<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\EventListener;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @group search
 * @dbIsolationPerTest
 */
class IndexListenerTest extends WebTestCase
{
    use MessageQueueAssertTrait;

    protected function setUp()
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

        self::assertMessageSent(Topics::INDEX_ENTITIES, [
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

        self::assertMessageSent(Topics::INDEX_ENTITIES, [
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

        self::assertMessageSent(Topics::INDEX_ENTITIES, [
            'class' => Item::class,
            'entityIds' => [$itemId => $itemId],
        ]);
    }

    /**
     * @return RegistryInterface
     */
    private function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }
}
