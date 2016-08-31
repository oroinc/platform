<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Client;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\DriverInterface;

class DriverTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $driver = $this->getContainer()->get('oro_message_queue.client.driver');

        $this->assertInstanceOf(DriverInterface::class, $driver);
    }
}
