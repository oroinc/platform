<?php


namespace Oro\Bundle\ThemeBundle\Tests\Unit\Model;

use Oro\Bundle\ThemeBundle\Model\Theme;

class ThemeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Theme
     */
    protected $theme;

    protected function setUp()
    {
        $this->theme = new Theme('test');
    }

    public function testGetNameAndConstructor()
    {
        $this->assertEquals('test', $this->theme->getName());
    }

    public function testIconMethods()
    {
        $this->assertNull($this->theme->getIcon());
        $this->theme->setIcon('favicon.ico');
        $this->assertEquals('favicon.ico', $this->theme->getIcon());
    }

    public function testLogoMethods()
    {
        $this->assertNull($this->theme->getIcon());
        $this->theme->setIcon('logo.png');
        $this->assertEquals('logo.png', $this->theme->getIcon());
    }

    public function testScreenshotMethods()
    {
        $this->assertNull($this->theme->getScreenshot());
        $this->theme->setScreenshot('screenshot.png');
        $this->assertEquals('screenshot.png', $this->theme->getScreenshot());
    }

    public function testStylesMethods()
    {
        $this->assertEquals(array(), $this->theme->getStyles());
        $this->theme->setStyles(array('styles.png'));
        $this->assertEquals(array('styles.png'), $this->theme->getStyles());
    }
}
