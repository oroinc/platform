<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;

class ConfigUpdateEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigUpdateEvent */
    protected $event;

    /** @var ConfigManager */
    protected $cm;

    /** @var array */
    protected $testUpdatedConfigValues
        = [
            'oro_user___level' => [
                'value' => 50,
            ]
        ];

    /** @var array */
    protected $testDeletedConfigValues
        = [
            ['oro_user', 'greeting'],
            ['oro_user', 'testNotChangedValue']
        ];


    public function setUp()
    {
        $this->cm = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->cm->expects($this->exactly(5))->method('get')
            ->will($this->onConsecutiveCalls('old value', 'default value', 'old value', 'old value', 'pre value'));

        $this->event = new ConfigUpdateEvent($this->cm, $this->testUpdatedConfigValues, $this->testDeletedConfigValues);
    }

    public function testChangeSet()
    {
        $result = $this->event->getChangeSet();

        $this->assertEquals(
            [
            'oro_user.greeting' =>
                [
                    'old' => "old value",
                    'new' => "default value"
                ],
            'oro_user.level'    =>
                [
                    'old' => "pre value",
                    'new' => 50
                ]
            ],
            $result
        );
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
                    'old' => "old value",
                    'new' => "default value"
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
