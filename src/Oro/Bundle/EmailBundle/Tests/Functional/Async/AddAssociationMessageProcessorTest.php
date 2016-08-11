<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\AddAssociationMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AddAssociationMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.processor.add_association');

        $this->assertInstanceOf(AddAssociationMessageProcessor::class, $service);
    }
}
