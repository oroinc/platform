<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\PurgeEmailAttachmentsMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PurgeEmailAttachmentsMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.purge_email_attachments');

        $this->assertInstanceOf(PurgeEmailAttachmentsMessageProcessor::class, $service);
    }
}
