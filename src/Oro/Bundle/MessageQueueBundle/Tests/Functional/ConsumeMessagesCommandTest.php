<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\ZeroConfig\ConsumeMessagesCommand;

class ConsumeMessagesCommandTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $queueConsumer = $this->getContainer()->get('oro_message_queue.zero_config.consume_messages_command');

        $this->assertInstanceOf(ConsumeMessagesCommand::class, $queueConsumer);
    }
}
