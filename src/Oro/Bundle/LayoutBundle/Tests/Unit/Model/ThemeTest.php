<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Model;

use Oro\Bundle\LayoutBundle\Model\Theme;

class ThemeTest extends \PHPUnit_Framework_TestCase
{
    /** @var Theme */
    protected $theme;

    protected function setUp()
    {
        $this->theme = new Theme('test');
    }

    protected function tearDown()
    {
        unset($this->theme);
    }

    public function testGetNameAndConstructor()
    {
        $this->assertEquals('test', $this->theme->getName());
    }

    public function testDirectoryMethods()
    {
        $this->assertNotEmpty($this->theme->getDirectory());
        $this->assertEquals('test', $this->theme->getDirectory());

        $this->theme->setDirectory('base');
        $this->assertEquals('base', $this->theme->getDirectory());
    }

    public function testLabelMethods()
    {
        $this->assertNull($this->theme->getLabel());
        $this->theme->setLabel('Oro Base theme');
        $this->assertEquals('Oro Base theme', $this->theme->getLabel());
    }

    public function testLogoMethods()
    {
        $this->assertNull($this->theme->getLogo());
        $this->theme->setLogo('logo.png');
        $this->assertEquals('logo.png', $this->theme->getLogo());
    }

    public function testScreenshotMethods()
    {
        $this->assertNull($this->theme->getScreenshot());
        $this->theme->setScreenshot('screenshot.png');
        $this->assertEquals('screenshot.png', $this->theme->getScreenshot());
    }

    public function testHiddenMethods()
    {
        $this->assertFalse($this->theme->isHidden());

        $this->theme->setHidden(true);
        $this->assertTrue($this->theme->isHidden());
    }

    public function testParentThemeMethods()
    {
        $this->assertNull($this->theme->getParentTheme());

        $this->theme->setParentTheme('base');
        $this->assertEquals('base', $this->theme->getParentTheme());
    }
}
