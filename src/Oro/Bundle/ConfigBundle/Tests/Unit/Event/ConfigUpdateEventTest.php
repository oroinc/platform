<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;

class ConfigUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    private ConfigUpdateEvent $event;

    private array $changeSet = [
        'oro_user.greeting' => [
            'old' => 'old value',
            'new' => 'default value'
        ],
        'oro_user.level'    => [
            'old' => 'pre value',
            'new' => 50
        ]
    ];

    protected function setUp(): void
    {
        $this->event = new ConfigUpdateEvent($this->changeSet);
    }

    /**
     * @dataProvider changeSetProvider
     */
    public function testChangeSet(mixed $changeSet)
    {
        $event = new ConfigUpdateEvent($changeSet);
        $this->assertEquals($this->changeSet, $event->getChangeSet());
    }

    public function changeSetProvider(): array
    {
        return [
            'object' => [new ConfigChangeSet($this->changeSet)],
            'array'  => [$this->changeSet]
        ];
    }

    /**
     * @dataProvider isChangedDataProvider
     */
    public function testIsChanged(string $key, bool $result)
    {
        $this->assertEquals($result, $this->event->isChanged($key));
    }

    public function isChangedDataProvider(): array
    {
        return [
            'oro_user.greeting changed'                => ['oro_user.greeting', true],
            'oro_user.level changed'                   => ['oro_user.level', true],
            'oro_user.testNotChangedValue not changed' => ['oro_user.testNotChangedValue', false],
        ];
    }

    /**
     * @dataProvider valueRetrievingDataProvider
     */
    public function testValueRetrieving(string $key, array $expectedValues, string $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
        }

        $new = $this->event->getNewValue($key);
        $old = $this->event->getOldValue($key);

        $this->assertEquals($expectedValues['old'], $old);
        $this->assertEquals($expectedValues['new'], $new);
    }

    public function valueRetrievingDataProvider(): array
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
                \LogicException::class
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForScopes
     */
    public function testScopes(?string $scope, ?int $scopeId)
    {
        $event = new ConfigUpdateEvent([], $scope, $scopeId);

        $this->assertEquals($scope, $event->getScope());
        $this->assertEquals($scopeId, $event->getScopeId());
    }

    public function dataProviderForScopes(): array
    {
        return [
            ['website', null],
            ['website', 1],
            [null, 1],
            [null, null],
        ];
    }
}
