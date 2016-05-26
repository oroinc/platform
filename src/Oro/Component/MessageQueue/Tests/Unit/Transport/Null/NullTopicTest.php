<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Null;

use Oro\Component\MessageQueue\Transport\Null\NullTopic;
use Oro\Component\MessageQueue\Transport\Topic;
use Oro\Component\Testing\ClassExtensionTrait;

class NullTopicTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTopicInterface()
    {
        $this->assertClassImplements(Topic::class, NullTopic::class);
    }

    public function testCouldBeConstructedWithNameAsArgument()
    {
        new NullTopic('aName');
    }

    public function testShouldAllowGetNameSetInConstructor()
    {
        $topic = new NullTopic('theName');

        $this->assertEquals('theName', $topic->getTopicName());
    }
}
