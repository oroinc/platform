<?php
namespace Oro\Bundle\ImapBundle\Tests\Functional\Async;

use Oro\Bundle\ImapBundle\Async\SyncEmailsMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SyncEmailsMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_imap.async.sync_emails');

        $this->assertInstanceOf(SyncEmailsMessageProcessor::class, $service);
    }
}
