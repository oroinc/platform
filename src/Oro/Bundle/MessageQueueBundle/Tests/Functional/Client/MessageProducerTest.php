<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Client;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class MessageProducerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $messageProducer = $this->getContainer()->get('oro_message_queue.client.message_producer');

        $this->assertInstanceOf(MessageProducerInterface::class, $messageProducer);
    }

    public function testCouldBeGetFromContainerAsShortenAlias()
    {
        $messageProducer = $this->getContainer()->get('oro_message_queue.client.message_producer');
        $aliasMessageProducer = $this->getContainer()->get('oro_message_queue.message_producer');

        $this->assertSame($messageProducer, $aliasMessageProducer);
    }
}
