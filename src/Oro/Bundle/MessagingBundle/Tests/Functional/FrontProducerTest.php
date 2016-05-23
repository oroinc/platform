<?php
namespace Oro\Bundle\MessagingBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Messaging\ZeroConfig\FrontProducer;

class FrontProducerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $frontProducer = $this->getContainer()->get('oro_messaging.zero_config.front_producer');

        $this->assertInstanceOf(FrontProducer::class, $frontProducer);
    }
}
