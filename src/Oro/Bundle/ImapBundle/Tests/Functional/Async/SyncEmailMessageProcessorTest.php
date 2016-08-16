<?php
namespace Oro\Bundle\ImapBundle\Tests\Functional\Async;

use Oro\Bundle\ImapBundle\Async\SyncEmailMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SyncEmailMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_imap.async.processor.sync_email');

        $this->assertInstanceOf(SyncEmailMessageProcessor::class, $service);
    }
}
