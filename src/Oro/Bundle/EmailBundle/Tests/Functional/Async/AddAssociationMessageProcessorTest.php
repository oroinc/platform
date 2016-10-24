<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\AddAssociationToEmailsMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AddAssociationToEmailsMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.add_association_to_emails');

        $this->assertInstanceOf(AddAssociationToEmailsMessageProcessor::class, $service);
    }
}
