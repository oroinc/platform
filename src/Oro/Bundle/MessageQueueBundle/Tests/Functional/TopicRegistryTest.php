<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\Meta\TopicMetaRegistry;

class TopicRegistryTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }
    
    public function testCouldBeGetFromContainerAsService()
    {
        $connection = $this->getContainer()->get('oro_message_queue.client.meta.topic_meta_registry');
        
        $this->assertInstanceOf(TopicMetaRegistry::class, $connection);
    }
}
