<?php
namespace Oro\Bundle\MessagingBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Messaging\ZeroConfig\ConsumeMessagesCommand;

class ConsumeMessagesCommandTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $queueConsumer = $this->getContainer()->get('oro_messaging.zero_config.consume_messages_command');

        $this->assertInstanceOf(ConsumeMessagesCommand::class, $queueConsumer);
    }
}
