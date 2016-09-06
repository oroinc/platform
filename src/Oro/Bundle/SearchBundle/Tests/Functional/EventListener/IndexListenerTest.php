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
        $this->getMessageProducer()->enable();
        $this->getMessageProducer()->clear();

        // test
        $item = new Item();
        $em->persist($item);
        $em->flush();

        $messages = $this->getMessageProducer()->getSentMessages();

        $this->assertNotNull($item->getId());
        $this->assertCount(1, $messages);
        $this->assertEquals(Topics::INDEX_ENTITIES, $messages[0]['topic']);

        $expectedMessage = [
            [
                'class' => Item::class,
                'id' => $item->getId(),
            ],
        ];

        $this->assertEquals($expectedMessage, $messages[0]['message']);
    }

    public function testShouldUpdateSearchIndexForEntityIfItWasUpdated()
    {
        $em = $this->getDoctrine()->getManagerForClass(Item::class);

        $item = new Item();
        $em->persist($item);
        $em->flush();
        $this->getMessageProducer()->enable();
        $this->getMessageProducer()->clear();

        // test
        $item->stringValue = 'value';
        $em->flush();

        $messages = $this->getMessageProducer()->getSentMessages();

        $this->assertNotEmpty($item->getId());
        $this->assertCount(1, $messages);
        $this->assertEquals(Topics::INDEX_ENTITIES, $messages[0]['topic']);

        $expectedMessage = [
            [
                'class' => Item::class,
                'id' => $item->getId(),
            ],
        ];

        $this->assertEquals($expectedMessage, $messages[0]['message']);
    }

    public function testShouldDeleteSearchIndexForEntityIfItWasDeleted()
    {
        $em = $this->getDoctrine()->getManagerForClass(Item::class);

        $item = new Item();
        $em->persist($item);
        $em->flush();
        $this->getMessageProducer()->enable();
        $this->getMessageProducer()->clear();

        $itemId = $item->getId();
        $this->assertNotNull($itemId);

        // test
        $em->remove($item);
        $em->flush();

        $messages = $this->getMessageProducer()->getSentMessages();

        $this->assertCount(1, $messages);
        $this->assertEquals(Topics::INDEX_ENTITIES, $messages[0]['topic']);

        $expectedMessage = [
            [
                'class' => Item::class,
                'id' => $itemId,
            ],
        ];

        $this->assertEquals($expectedMessage, $messages[0]['message']);
    }

    /**
     * @return RegistryInterface
     */
    private function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * @return \Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector
     */
    private function getMessageProducer()
    {
        return $this->getContainer()->get('oro_message_queue.client.message_producer');
    }
}
