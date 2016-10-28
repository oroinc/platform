<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\AddAssociationToEmailMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AddAssociationToEmailMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.add_association_to_email');

        $this->assertInstanceOf(AddAssociationToEmailMessageProcessor::class, $service);
    }
}
