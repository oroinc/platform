<?php
namespace Oro\Component\Messaging\Tests\Transport\Null;

use Oro\Component\Messaging\Transport\Null\NullQueue;
use Oro\Component\Testing\ClassExtensionTrait;

class NullQueueTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementQueueInterface()
    {
        $this->assertClassImplements(
            'Oro\Component\Messaging\Transport\Queue',
            'Oro\Component\Messaging\Transport\Null\NullQueue'
        );
    }

    public function testCouldBeConstructedWithNameAsArgument()
    {
        new NullQueue('aName');
    }

    public function testShouldAllowGetNameSetInConstructor()
    {
        $queue = new NullQueue('theName');

        $this->assertEquals('theName', $queue->getQueueName());
    }
}
