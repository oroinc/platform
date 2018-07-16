<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;

class ConfigUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigUpdateEvent */
    protected $event;

    /** @var array */
    protected $changeSet = [
        'oro_user.greeting' => [
            'old' => 'old value',
            'new' => 'default value'
        ],
        'oro_user.level'    => [
            'old' => 'pre value',
            'new' => 50
        ]
    ];


    protected function setUp()
    {
        $this->event = new ConfigUpdateEvent($this->changeSet);
    }

    /**
     * @dataProvider changeSetProvider
     */
    public function testChangeSet($changeSet)
    {
        $event = new ConfigUpdateEvent($changeSet);
        $this->assertEquals($this->changeSet, $event->getChangeSet());
    }

    public function changeSetProvider()
    {
        return [
            'object' => [new ConfigChangeSet($this->changeSet)],
            'array'  => [$this->changeSet]
        ];
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
            $this->expectException($exception);
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

    /**
     * @dataProvider dataProviderForScopes
     * @param string|null $scope
     * @param int|null    $scopeId
     */
    public function testScopes($scope, $scopeId)
    {
        $event = new ConfigUpdateEvent([], $scope, $scopeId);

        $this->assertEquals($scope, $event->getScope());
        $this->assertEquals($scopeId, $event->getScopeId());
    }

    /**
     * @return array
     */
    public function dataProviderForScopes()
    {
        return [
            ['website', null],
            ['website', 1],
            [null, 1],
            [null, null],
        ];
    }
}
