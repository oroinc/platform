<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\UpdateEmailOwnerAssociationsMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class UpdateEmailOwnerAssociationsMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.update_email_owner_associations');

        $this->assertInstanceOf(UpdateEmailOwnerAssociationsMessageProcessor::class, $service);
    }
}
