<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\EventListener;

use Oro\Bundle\EmailBundle\EventListener\AutoResponseListener;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AutoResponseListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.listener.auto_response_listener');

        $this->assertInstanceOf(AutoResponseListener::class, $service);
    }
}
