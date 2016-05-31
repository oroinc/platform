<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption\Extention;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\DoctrineClearIdentityMapExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DoctrineClearIdentityMapExtensionTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $connection = $this->getContainer()->get('oro_message_queue.consumption.docrine_clear_identity_map_extension');

        $this->assertInstanceOf(DoctrineClearIdentityMapExtension::class, $connection);
    }
}
