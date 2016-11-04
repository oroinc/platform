<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\AddEmailAssociationMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AddEmailAssociationMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.add_email_association');

        $this->assertInstanceOf(AddEmailAssociationMessageProcessor::class, $service);
    }
}
