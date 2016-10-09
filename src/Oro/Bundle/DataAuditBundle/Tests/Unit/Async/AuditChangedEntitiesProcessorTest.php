<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Util\Async;

use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesProcessor;
use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\Testing\ClassExtensionTrait;

class AuditChangedEntitiesProcessorTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;
    
    public function testShouldSubscribeToEntitiesChangedTopic()
    {
        $this->assertClassImplements(TopicSubscriberInterface::class, AuditChangedEntitiesProcessor::class);
        $this->assertEquals(
            [Topics::ENTITIES_CHANGED],
            AuditChangedEntitiesProcessor::getSubscribedTopics()
        );
    }
}
