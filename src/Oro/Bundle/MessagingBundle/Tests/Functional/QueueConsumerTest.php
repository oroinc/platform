<?php
namespace Oro\Bundle\MessagingBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Messaging\Consumption\QueueConsumer;

class QueueConsumerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }
    
    public function testCouldBeGetFromContainerAsService()
    {
        $queueConsumer = $this->getContainer()->get('oro_messaging.consumption.queue_consumer');
        
        $this->assertInstanceOf(QueueConsumer::class, $queueConsumer);
    }
}
