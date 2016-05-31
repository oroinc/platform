<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption\Extention;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\DoctrinePingConnectionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DoctrinePingConnectionExtensionTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $connection = $this->getContainer()->get('oro_message_queue.consumption.docrine_ping_connection_extension');

        $this->assertInstanceOf(DoctrinePingConnectionExtension::class, $connection);
    }
}
