<?php
namespace Oro\Component\MessageQueue\Tests\Unit\ZeroConfig;

use Oro\Component\MessageQueue\ZeroConfig\Topic;

class TopicTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithNameAndDescriptionAsArguments()
    {
        new Topic('aName', 'aDescription');
    }
    
    public function testShouldAllowGetNameSetInConstructor()
    {
        $topic = new Topic('theName', 'aDescription');
        
        $this->assertSame('theName', $topic->getName());
    }
    
    public function testShouldAllowGetDescriptionSetInConstructor()
    {
        $topic = new Topic('aName', 'theDescription');

        $this->assertSame('theDescription', $topic->getDescription());
    }
}
