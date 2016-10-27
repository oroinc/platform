<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\Async;

use Oro\Bundle\ImapBundle\Async\ClearInactiveMailboxMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ClearInactiveMailboxMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_imap.async.clear_inactive_mailbox_message_processor');

        $this->assertInstanceOf(ClearInactiveMailboxMessageProcessor::class, $instance);
    }
}
