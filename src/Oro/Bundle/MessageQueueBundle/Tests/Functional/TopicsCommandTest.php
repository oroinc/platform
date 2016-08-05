<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\Meta\TopicsCommand;

class TopicsCommandTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $command = $this->getContainer()->get('oro_message_queue.client.meta.topics_command');

        $this->assertInstanceOf(TopicsCommand::class, $command);
    }
}
