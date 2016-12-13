<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\UpdateEmailOwnerAssociationMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class UpdateEmailOwnerAssociationMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.update_email_owner_association');

        $this->assertInstanceOf(UpdateEmailOwnerAssociationMessageProcessor::class, $service);
    }
}
