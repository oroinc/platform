<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\ZeroConfig\TopicsDebugCommand;

class TopicsDebugCommandTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $command = $this->getContainer()->get('oro_message_queue.zero_config.topics_debug_messages');

        $this->assertInstanceOf(TopicsDebugCommand::class, $command);
    }
}
