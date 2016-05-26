<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;

class QueueConsumerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }
    
    public function testCouldBeGetFromContainerAsService()
    {
        $queueConsumer = $this->getContainer()->get('oro_message_queue.consumption.queue_consumer');
        
        $this->assertInstanceOf(QueueConsumer::class, $queueConsumer);
    }
}
