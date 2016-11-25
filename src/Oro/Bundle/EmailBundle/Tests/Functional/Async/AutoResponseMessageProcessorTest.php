<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\AutoResponseMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AutoResponseMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.auto_response');

        $this->assertInstanceOf(AutoResponseMessageProcessor::class, $service);
    }
}
