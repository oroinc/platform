<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;

class DestinationRegistryTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }
    
    public function testCouldBeGetFromContainerAsService()
    {
        $connection = $this->getContainer()->get('oro_message_queue.client.meta.destination_meta_registry');
        
        $this->assertInstanceOf(DestinationMetaRegistry::class, $connection);
    }
}
