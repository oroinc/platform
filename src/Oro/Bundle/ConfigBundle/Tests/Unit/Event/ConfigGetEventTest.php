<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigGetEvent;

class ConfigGetEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
    }

    public function testEvent()
    {
        $key = 'key';
        $value = 'value';
        $full = true;
        $scopeId = 1;

        $event = new ConfigGetEvent($this->configManager, $key, $value, $full, $scopeId);

        $this->assertSame($this->configManager, $event->getConfigManager());
        $this->assertEquals($key, $event->getKey());
        $this->assertEquals($value, $event->getValue());
        $this->assertEquals($full, $event->isFull());
        $this->assertEquals($scopeId, $event->getScopeId());

        $newValue = 'new_value';
        $event->setValue($newValue);
        $this->assertEquals($newValue, $event->getValue());
    }
}
