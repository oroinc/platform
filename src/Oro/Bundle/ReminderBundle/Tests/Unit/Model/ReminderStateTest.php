<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model;

use Oro\Bundle\ReminderBundle\Model\ReminderState;

class ReminderStateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider typesProvider
     */
    public function testCreate($types)
    {
        new ReminderState($types);
    }

    /**
     * @dataProvider typesProvider
     */
    public function testToArray($types)
    {
        $state = new ReminderState($types);

        $this->assertEquals($types, $state->toArray());
    }

    /**
     * @dataProvider typesProvider
     */
    public function testSerialize($types)
    {
        $state = new ReminderState($types);

        $this->assertEquals(serialize($types), $state->serialize());
    }

    /**
     * @dataProvider typesProvider
     */
    public function testUnserialize($types)
    {
        $state = new ReminderState($types);
        $state->unserialize($state->serialize());

        $this->assertEquals($types, $state->toArray());
    }

    /**
     * @dataProvider typesProvider
     */
    public function testOffsetExists($types, $offset, $value, $offsetExists)
    {
        $state = new ReminderState($types);

        $this->$offsetExists($state->offsetExists($offset));
    }

    /**
     * @dataProvider typesProvider
     */
    public function testOffsetGet($types, $offset, $value)
    {
        $state = new ReminderState($types);

        $this->assertEquals($value, $state->offsetGet($offset));
    }

    /**
     * @dataProvider typesProvider
     */
    public function testOffsetSet($types, $offset, $value)
    {
        $state = new ReminderState($types);

        $state->offsetSet($offset, $value . $value);

        $this->assertEquals($value . $value, $state->offsetGet($offset));
    }

    /**
     * @dataProvider typesProvider
     */
    public function testOffsetUnset($types, $offset)
    {
        $state = new ReminderState($types);

        $state->offsetUnset($offset);

        $this->assertFalse($state->offsetExists($offset));
    }

    public function typesProvider()
    {
        return [
            'empty' => [
                'types' => [],
                'offset' => 'type1',
                'value' => null,
                'offsetExists' => 'assertFalse',
            ],
            'not_empty' => [
                'types' => ['type1' => true, 'type2' => false],
                'offset' => 'type1',
                'value' => true,
                'offsetExists' => 'assertTrue',
            ],
        ];
    }
}
