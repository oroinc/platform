<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Util\Async;

use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesInverseRelationsProcessor;
use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\Testing\ClassExtensionTrait;

class AuditChangedEntitiesInverseRelationsProcessorTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldSubscribeToEntitiesInversedRelationsChangedTopic()
    {
        $this->assertClassImplements(
            TopicSubscriberInterface::class,
            AuditChangedEntitiesInverseRelationsProcessor::class
        );

        $this->assertEquals(
            [Topics::ENTITIES_INVERSED_RELATIONS_CHANGED],
            AuditChangedEntitiesInverseRelationsProcessor::getSubscribedTopics()
        );
    }
}
