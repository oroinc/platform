<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Util\Async;

use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesRelationsProcessor;
use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\Testing\ClassExtensionTrait;

class AuditChangedEntitiesRelationsProcessorTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldSubscribeToEntitiesRelationsChangedTopic()
    {
        $this->assertClassImplements(
            TopicSubscriberInterface::class,
            AuditChangedEntitiesRelationsProcessor::class
        );

        $this->assertEquals(
            [Topics::ENTITIES_RELATIONS_CHANGED],
            AuditChangedEntitiesRelationsProcessor::getSubscribedTopics()
        );
    }
}
