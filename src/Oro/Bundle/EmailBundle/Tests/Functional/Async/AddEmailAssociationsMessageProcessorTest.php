<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\AddEmailAssociationsMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AddEmailAssociationsMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.add_email_associations');

        $this->assertInstanceOf(AddEmailAssociationsMessageProcessor::class, $service);
    }
}
