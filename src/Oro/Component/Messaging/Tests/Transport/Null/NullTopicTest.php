<?php
namespace Oro\Component\Messaging\Tests\Transport\Null;

use Oro\Component\Messaging\Transport\Null\NullTopic;
use Oro\Component\Testing\ClassExtensionTrait;

class NullTopicTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTopicInterface()
    {
        $this->assertClassImplements(
            'Oro\Component\Messaging\Transport\Topic',
            'Oro\Component\Messaging\Transport\Null\NullTopic'
        );
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
