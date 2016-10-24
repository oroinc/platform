<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\AutoResponsesMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AutoResponsesMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.auto_responses');

        $this->assertInstanceOf(AutoResponsesMessageProcessor::class, $service);
    }
}
