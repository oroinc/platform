<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Meta\TopicMeta;

class TopicMetaTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithNameOnly()
    {
        $topic = new TopicMeta('aName');
        
        $this->assertAttributeEquals('aName', 'name', $topic);
        $this->assertAttributeEquals('', 'description', $topic);
        $this->assertAttributeEquals([], 'subscribers', $topic);
    }
    
    public function testCouldBeConstructedWithNameAndDescriptionOnly()
    {
        $topic = new TopicMeta('aName', 'aDescription');

        $this->assertAttributeEquals('aName', 'name', $topic);
        $this->assertAttributeEquals('aDescription', 'description', $topic);
        $this->assertAttributeEquals([], 'subscribers', $topic);
    }

    public function testCouldBeConstructedWithNameAndDescriptionAndSubscribers()
    {
        $topic = new TopicMeta('aName', 'aDescription', ['aSubscriber']);

        $this->assertAttributeEquals('aName', 'name', $topic);
        $this->assertAttributeEquals('aDescription', 'description', $topic);
        $this->assertAttributeEquals(['aSubscriber'], 'subscribers', $topic);
    }
    
    public function testShouldAllowGetNameSetInConstructor()
    {
        $topic = new TopicMeta('theName', 'aDescription');
        
        $this->assertSame('theName', $topic->getName());
    }
    
    public function testShouldAllowGetDescriptionSetInConstructor()
    {
        $topic = new TopicMeta('aName', 'theDescription');

        $this->assertSame('theDescription', $topic->getDescription());
    }

    public function testShouldAllowGetSubscribersSetInConstructor()
    {
        $topic = new TopicMeta('aName', '', ['aSubscriber']);

        $this->assertSame(['aSubscriber'], $topic->getSubscribers());
    }
}
