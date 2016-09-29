<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\PurgeEmailAttachmentMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PurgeEmailAttachmentMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.processor.purge_email_attachment');

        $this->assertInstanceOf(PurgeEmailAttachmentMessageProcessor::class, $service);
    }
}
