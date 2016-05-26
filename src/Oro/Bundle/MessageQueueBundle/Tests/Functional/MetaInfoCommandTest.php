<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\ZeroConfig\Meta\MetaInfoCommand;

class MetaInfoCommandTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $command = $this->getContainer()->get('oro_message_queue.zero_config.meta.meta_info_command');

        $this->assertInstanceOf(MetaInfoCommand::class, $command);
    }
}
