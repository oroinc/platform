<?php


namespace Oro\Bundle\ThemeBundle\Tests\Unit\Model;

use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

class ThemeRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ThemeRegistry
     */
    protected $themeRegistry;

    protected $themeSettings = array(
        'foo' => array(
            'label' => 'Foo Theme',
            'styles' => array('style.css'),
            'icon' => 'favicon.ico',
            'logo' => 'logo.png',
            'screenshot' => 'screenshot.png'
        ),
        'bar' => array(
            'styles' => array('style.css')
        )
    );

    protected function setUp()
    {
        $this->themeRegistry = new ThemeRegistry($this->themeSettings);
    }

    public function testGetTheme()
    {
        $fooTheme = $this->themeRegistry->getTheme('foo');
        $this->assertInstanceOf('Oro\Bundle\ThemeBundle\Model\Theme', $fooTheme);
        $this->assertEquals(array('style.css'), $fooTheme->getStyles());
        $this->assertEquals('Foo Theme', $fooTheme->getLabel());
        $this->assertEquals('favicon.ico', $fooTheme->getIcon());
        $this->assertEquals('logo.png', $fooTheme->getLogo());
        $this->assertEquals('screenshot.png', $fooTheme->getScreenshot());
        $this->assertSame($fooTheme, $this->themeRegistry->getTheme('foo'));

        $barTheme = $this->themeRegistry->getTheme('bar');
        $this->assertInstanceOf('Oro\Bundle\ThemeBundle\Model\Theme', $barTheme);
        $this->assertEquals(array('style.css'), $barTheme->getStyles());
        $this->assertNull($barTheme->getLabel());
        $this->assertNull($barTheme->getIcon());
        $this->assertNull($barTheme->getLogo());
        $this->assertNull($barTheme->getScreenshot());
        $this->assertSame($barTheme, $this->themeRegistry->getTheme('bar'));

        $this->assertEquals(
            array('foo' => $fooTheme, 'bar' => $barTheme),
            $this->themeRegistry->getAllThemes()
        );
    }

    /**
     * @expectedException \Oro\Bundle\ThemeBundle\Exception\ThemeNotFoundException
     * @expectedExceptionMessage Theme "baz" not found.
     */
    public function testGetThemeNotFoundException()
    {
        $this->themeRegistry->getTheme('baz');
    }

    public function testGetActiveTheme()
    {
        $this->assertNull($this->themeRegistry->getActiveTheme());
        $this->themeRegistry->setActiveTheme('foo');
        $activeTheme = $this->themeRegistry->getActiveTheme();
        $this->assertInstanceOf('Oro\Bundle\ThemeBundle\Model\Theme', $activeTheme);
        $this->assertEquals('foo', $activeTheme->getName());
    }
}
