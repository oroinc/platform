<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigGetEvent;
use PHPUnit\Framework\TestCase;

class ConfigGetEventTest extends TestCase
{
    public function testEventWhenRequestedOnlyValue(): void
    {
        $configManager = $this->createMock(ConfigManager::class);
        $key = 'key';
        $value = 'value';
        $scope = 'scope';
        $scopeId = 1;

        $event = new ConfigGetEvent($configManager, $key, $value, false, $scope, $scopeId);
        self::assertSame($configManager, $event->getConfigManager());
        self::assertSame($key, $event->getKey());
        self::assertSame($value, $event->getValue());
        self::assertFalse($event->isFull());
        self::assertSame($scope, $event->getScope());
        self::assertSame($scopeId, $event->getScopeId());
    }

    public function testEventWhenRequestedFullInfo(): void
    {
        $configManager = $this->createMock(ConfigManager::class);
        $key = 'key';
        $value = 'value';
        $scope = 'scope';
        $scopeId = 1;

        $event = new ConfigGetEvent($configManager, $key, $value, true, $scope, $scopeId);
        self::assertSame($configManager, $event->getConfigManager());
        self::assertSame($key, $event->getKey());
        self::assertSame($value, $event->getValue());
        self::assertTrue($event->isFull());
        self::assertSame($scope, $event->getScope());
        self::assertSame($scopeId, $event->getScopeId());
    }

    public function testSetValue(): void
    {
        $value = 'value';
        $event = new ConfigGetEvent($this->createMock(ConfigManager::class), 'key', $value, false, 'scope', 1);
        self::assertSame($value, $event->getValue());

        $newValue = 'new_value';
        $event->setValue($newValue);
        self::assertSame($newValue, $event->getValue());
    }
}
