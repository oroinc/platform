<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\ZeroConfig\FrontProducer;

class FrontProducerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $frontProducer = $this->getContainer()->get('oro_message_queue.zero_config.front_producer');

        $this->assertInstanceOf(FrontProducer::class, $frontProducer);
    }
}
