<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Event;

use Oro\Component\Layout\Extension\Theme\Event\ThemeOptionGetEvent;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ThemeOptionGetEventTest extends TestCase
{
    private ThemeManager&MockObject $themeManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->event = new ThemeOptionGetEvent($this->themeManager, 'default', 'template');
    }

    public function testGetThemeManager(): void
    {
        self::assertSame($this->themeManager, $this->event->getThemeManager());
    }

    public function testGetThemeName(): void
    {
        self::assertSame('default', $this->event->getThemeName());
    }

    public function testGetOptionName(): void
    {
        self::assertSame('template', $this->event->getOptionName());
    }

    public function testIsInherited(): void
    {
        self::assertTrue($this->event->isInherited());

        $event = new ThemeOptionGetEvent($this->themeManager, 'default', 'template', false);
        self::assertFalse($event->isInherited());
    }

    public function testGetValue(): void
    {
        self::assertNull($this->event->getValue());
        $this->event->setValue('path/to/template');
        self::assertSame('path/to/template', $this->event->getValue());
    }
}
