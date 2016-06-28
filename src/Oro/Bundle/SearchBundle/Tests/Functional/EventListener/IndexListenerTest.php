<?php
namespace Oro\Bundle\SearchBundle\Tests\Functional\EventListener;

use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IndexListenerTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
        $this->startTransaction();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->rollbackTransaction();
    }

    public function testShouldCreateSearchIndexForEntityIfItWasCreated()
    {
        $em = $this->getDoctrine()->getManagerForClass(Item::class);
        $this->getMessageProducer()->clearTraces();

        // test
        $item = new Item();
        $em->persist($item);
        $em->flush();

        $traces = $this->getMessageProducer()->getTraces();

        $this->assertNotNull($item->getId());
        $this->assertCount(1, $traces);
        $this->assertEquals(Topics::INDEX_ENTITIES, $traces[0]['topic']);

        $expectedMessage = [
            [
                'class' => Item::class,
                'id' => $item->getId(),
            ],
        ];

        $this->assertEquals($expectedMessage, $traces[0]['message']);
    }

    public function testShouldUpdateSearchIndexForEntityIfItWasUpdated()
    {
        $em = $this->getDoctrine()->getManagerForClass(Item::class);

        $item = new Item();
        $em->persist($item);
        $em->flush();
        $this->getMessageProducer()->clearTraces();

        // test
        $item->stringValue = 'value';
        $em->flush();

        $traces = $this->getMessageProducer()->getTraces();

        $this->assertNotEmpty($item->getId());
        $this->assertCount(1, $traces);
        $this->assertEquals(Topics::INDEX_ENTITIES, $traces[0]['topic']);

        $expectedMessage = [
            [
                'class' => Item::class,
                'id' => $item->getId(),
            ],
        ];

        $this->assertEquals($expectedMessage, $traces[0]['message']);
    }

    public function testShouldDeleteSearchIndexForEntityIfItWasDeleted()
    {
        $em = $this->getDoctrine()->getManagerForClass(Item::class);

        $item = new Item();
        $em->persist($item);
        $em->flush();
        $this->getMessageProducer()->clearTraces();

        $itemId = $item->getId();
        $this->assertNotNull($itemId);

        // test
        $em->remove($item);
        $em->flush();

        $traces = $this->getMessageProducer()->getTraces();

        $this->assertCount(1, $traces);
        $this->assertEquals(Topics::INDEX_ENTITIES, $traces[0]['topic']);

        $expectedMessage = [
            [
                'class' => Item::class,
                'id' => $itemId,
            ],
        ];

        $this->assertEquals($expectedMessage, $traces[0]['message']);
    }

    /**
     * @return RegistryInterface
     */
    private function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * @return \Oro\Component\MessageQueue\Client\MessageProducer
     */
    private function getMessageProducer()
    {
        return $this->getContainer()->get('oro_message_queue.client.message_producer');
    }
}
