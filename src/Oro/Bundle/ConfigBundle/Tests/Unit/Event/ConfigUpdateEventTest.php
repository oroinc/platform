<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;

class ConfigUpdateEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigUpdateEvent */
    protected $event;

    /** @var array */
    protected $changeSet = [
        'oro_user.greeting' => [
            'old' => 'old value',
            'new' => 'default value'
        ],
        'oro_user.level' => [
            'old' => 'pre value',
            'new' => 50
        ]
    ];


    protected function setUp()
    {
        $this->event = new ConfigUpdateEvent($this->changeSet);
    }

    public function testChangeSet()
    {
        $this->assertEquals($this->changeSet, $this->event->getChangeSet());
    }

    /**
     * @dataProvider isChangedDataProvider
     *
     * @param string $key
     * @param bool   $result
     */
    public function testIsChanged($key, $result)
    {
        $this->assertEquals($result, $this->event->isChanged($key));
    }

    /**
     * @return array
     */
    public function isChangedDataProvider()
    {
        return [
            'oro_user.greeting changed'                => ['oro_user.greeting', true],
            'oro_user.level changed'                   => ['oro_user.level', true],
            'oro_user.testNotChangedValue not changed' => ['oro_user.testNotChangedValue', false],
        ];
    }

    /**
     * @dataProvider valueRetrievingDataProvider
     *
     * @param string $key
     * @param array  $expectedValues
     * @param bool   $exception
     *
     */
    public function testValueRetrieving($key, array $expectedValues, $exception = false)
    {
        if (false !== $exception) {
            $this->setExpectedException($exception);
        }

        $new = $this->event->getNewValue($key);
        $old = $this->event->getOldValue($key);

        $this->assertEquals($expectedValues['old'], $old);
        $this->assertEquals($expectedValues['new'], $new);
    }

    /**
     * @return array
     */
    public function valueRetrievingDataProvider()
    {
        return [
            'changed value'     => [
                'oro_user.greeting',
                [
                    'old' => 'old value',
                    'new' => 'default value'
                ]
            ],
            'not changed value' => [
                'oro_user.someValue',
                [],
                '\LogicException'
            ],
        ];
    }
}
