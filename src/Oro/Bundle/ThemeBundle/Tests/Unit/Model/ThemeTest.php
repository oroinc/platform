<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Model;

use Oro\Bundle\ThemeBundle\Model\Theme;
use PHPUnit\Framework\TestCase;

class ThemeTest extends TestCase
{
    private Theme $theme;

    #[\Override]
    protected function setUp(): void
    {
        $this->theme = new Theme('test');
    }

    public function testGetNameAndConstructor(): void
    {
        self::assertEquals('test', $this->theme->getName());
    }

    public function testIconMethods(): void
    {
        self::assertNull($this->theme->getIcon());
        $this->theme->setIcon('favicon.ico');
        self::assertEquals('favicon.ico', $this->theme->getIcon());
    }

    public function testLogoMethods(): void
    {
        self::assertNull($this->theme->getIcon());
        $this->theme->setIcon('logo.png');
        self::assertEquals('logo.png', $this->theme->getIcon());
    }

    public function testScreenshotMethods(): void
    {
        self::assertNull($this->theme->getScreenshot());
        $this->theme->setScreenshot('screenshot.png');
        self::assertEquals('screenshot.png', $this->theme->getScreenshot());
    }

    public function testRtlSupportMethods(): void
    {
        self::assertFalse($this->theme->isRtlSupport());
        $this->theme->setRtlSupport(true);
        self::assertTrue($this->theme->isRtlSupport());
    }
}
