<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsFormOptionsEvent;

class ConfigSettingsFormOptionsEventTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_OPTIONS = [
        'key1' => ['option1' => 'value1'],
        'key2' => ['option2' => 'value2']
    ];

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigSettingsFormOptionsEvent */
    private $event;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->event = new ConfigSettingsFormOptionsEvent($this->configManager, self::FORM_OPTIONS);
    }

    public function testGetConfigManager(): void
    {
        $this->assertSame($this->configManager, $this->event->getConfigManager());
    }

    public function testFormOptions(): void
    {
        $this->assertEquals(self::FORM_OPTIONS, $this->event->getAllFormOptions());

        $this->event->setFormOptions('key1', ['option1' => 'new_value']);
        $this->assertEquals(['option1' => 'new_value'], $this->event->getFormOptions('key1'));

        $newFormOptions = self::FORM_OPTIONS;
        $newFormOptions['key1']['option1'] = 'new_value';
        $this->assertEquals($newFormOptions, $this->event->getAllFormOptions());
    }

    public function testHasFormOptions(): void
    {
        $this->assertTrue($this->event->hasFormOptions('key1'));
        $this->assertFalse($this->event->hasFormOptions('key3'));
    }

    public function testGetFormOptionsForUndefinedConfigKey(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There are no form options for "key3".');
        $this->event->getFormOptions('key3');
    }

    public function testSetFormOptionsForUndefinedConfigKey(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There are no form options for "key3".');
        $this->event->setFormOptions('key3', []);
    }
}
