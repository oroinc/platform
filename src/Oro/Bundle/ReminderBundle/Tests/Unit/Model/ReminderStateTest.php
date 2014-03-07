<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model;

use Oro\Bundle\ReminderBundle\Model\ReminderState;

class ReminderStateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider typesDataProvider
     */
    public function testCreate($types)
    {
        $this->createReminderState($types);
    }

    /**
     * @dataProvider isAllSentDataProvider
     */
    public function testIsAllSent(array $types, $expected)
    {
        $reminderState = $this->createReminderState($types);
        $this->assertEquals($expected, $reminderState->isAllSent());
    }

    public function isAllSentDataProvider()
    {
        return array(
            'all_sent_true' => array(
                'types' => array('foo' => ReminderState::SEND_TYPE_SENT, 'bar' => ReminderState::SEND_TYPE_SENT),
                'expected' => true,
            ),
            'all_sent_false' => array(
                'types' => array('foo' => ReminderState::SEND_TYPE_SENT, 'bar' => ReminderState::SEND_TYPE_NOT_SENT),
                'expected' => false,
            ),
            'all_sent_true_empty' => array(
                'types' => array(),
                'expected' => true,
            ),
        );
    }

    /**
     * @dataProvider getSendTypeNamesDataProvider
     */
    public function testGetSendTypeNames(array $types, array $expected)
    {
        $reminderState = $this->createReminderState($types);
        $this->assertEquals($expected, $reminderState->getSendTypeNames());
    }

    public function getSendTypeNamesDataProvider()
    {
        return array(
            'default' => array(
                'types' => array('foo' => ReminderState::SEND_TYPE_SENT, 'bar' => ReminderState::SEND_TYPE_SENT),
                'expected' => array('foo', 'bar'),
            ),
            'empty' => array(
                'types' => array(),
                'expected' => array(),
            )
        );
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testHasSendTypeState($types, $offset, $value, $offsetExists)
    {
        $state = $this->createReminderState($types);

        $this->$offsetExists($state->hasSendTypeState($offset));
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testGetSendTypeState($types, $name, $value)
    {
        $state = $this->createReminderState($types);

        $this->assertEquals($value, $state->getSendTypeState($name));
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testSetSendTypeState($types, $name, $value)
    {
        $state = $this->createReminderState($types);

        $state->setSendTypeState($name, $value);

        $this->assertEquals($value, $state->getSendTypeState($name));
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testToArray($types)
    {
        $state = $this->createReminderState($types);

        $this->assertEquals($types, $state->toArray());
    }

    /**
     * @dataProvider serializeDataProvider
     */
    public function testSerialize(array $types, $serialized)
    {
        $state = $this->createReminderState($types);

        $this->assertEquals($serialized, $state->serialize());
    }

    /**
     * @dataProvider serializeDataProvider
     */
    public function testUnserialize(array $types, $serialized)
    {
        $expectedState = $this->createReminderState($types);
        $actualState = $this->createReminderState();
        $actualState->unserialize($serialized);

        $this->assertEquals($expectedState, $actualState);
    }

    public function serializeDataProvider()
    {
        return array(
            'default' => array(
                'types' => array('foo' => 'bar', 'bar' => 'baz'),
                'serialized' => 'foo=bar&bar=baz',
            ),
            'empty' => array(
                'types' => array(),
                'serialized' => '',
            )
        );
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testOffsetExists($types, $offset, $value, $offsetExists)
    {
        $state = $this->createReminderState($types);

        $this->$offsetExists($state->offsetExists($offset));
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testOffsetGet($types, $offset, $value)
    {
        $state = $this->createReminderState($types);

        $this->assertEquals($value, $state->offsetGet($offset));
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testOffsetSet($types, $offset, $value)
    {
        $state = $this->createReminderState($types);

        $state->offsetSet($offset, $value . $value);

        $this->assertEquals($value . $value, $state->offsetGet($offset));
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testOffsetUnset($types, $offset)
    {
        $state = $this->createReminderState($types);

        $state->offsetUnset($offset);

        $this->assertFalse($state->offsetExists($offset));
    }

    public function typesDataProvider()
    {
        return [
            'empty' => [
                'types' => [],
                'offset' => 'type1',
                'value' => null,
                'offsetExists' => 'assertFalse',
            ],
            'not_empty' => [
                'types' => ['type1' => ReminderState::SEND_TYPE_SENT, 'type2' => ReminderState::SEND_TYPE_NOT_SENT],
                'offset' => 'type1',
                'value' => ReminderState::SEND_TYPE_SENT,
                'offsetExists' => 'assertTrue',
            ],
        ];
    }

    /**
     * @param array $types
     * @return ReminderState
     */
    protected function createReminderState(array $types = array())
    {
        return new ReminderState($types);
    }
}
