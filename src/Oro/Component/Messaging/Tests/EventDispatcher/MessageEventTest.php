<?php
namespace Oro\Component\Messaging\Tests\EventDispatcher;

use Oro\Component\Messaging\EventDispatcher\MessageEvent;

class MessageEventTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithoutArguments()
    {
        new MessageEvent();
    }

    public function testCouldBeConstructedWithValueAsArgument()
    {
        new MessageEvent('some-value');
    }

    public function testCouldSetValuesViaConstructor()
    {
        $values = ['key' => 'value'];

        $event = new MessageEvent($values);

        $this->assertEquals($values, $event->getValues());
    }

    public function testCouldSetValuesViaSetter()
    {
        $values = ['key' => 'value'];

        $event = new MessageEvent(['key1' => 'value1']);
        $event->setValues($values);

        $this->assertEquals($values, $event->getValues());
    }
}
