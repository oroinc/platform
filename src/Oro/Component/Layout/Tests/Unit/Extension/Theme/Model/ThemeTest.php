<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class ThemeTest extends TestCase
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

    public function testDirectoryMethods(): void
    {
        self::assertNotEmpty($this->theme->getDirectory());
        self::assertEquals('test', $this->theme->getDirectory());

        $this->theme->setDirectory('base');
        self::assertEquals('base', $this->theme->getDirectory());
    }

    public function testLabelMethods(): void
    {
        self::assertNull($this->theme->getLabel());
        $this->theme->setLabel('Oro Base theme');
        self::assertEquals('Oro Base theme', $this->theme->getLabel());
    }

    public function testIconMethods(): void
    {
        self::assertNull($this->theme->getIcon());
        $this->theme->setIcon('icon.ico');
        self::assertEquals('icon.ico', $this->theme->getIcon());
    }

    public function testLogoMethods(): void
    {
        self::assertNull($this->theme->getLogo());
        $this->theme->setLogo('logo.png');
        self::assertEquals('logo.png', $this->theme->getLogo());
    }

    public function testImagePlaceholdersMethods(): void
    {
        self::assertEmpty($this->theme->getImagePlaceholders());
        $this->theme->setImagePlaceholders(['test' => '/test/url.png']);
        self::assertEquals(['test' => '/test/url.png'], $this->theme->getImagePlaceholders());
    }

    public function testRtlSupport(): void
    {
        self::assertFalse($this->theme->isRtlSupport());
        $this->theme->setRtlSupport(true);
        self::assertTrue($this->theme->isRtlSupport());
    }

    public function testSvgIconsSupport(): void
    {
        self::assertNull($this->theme->isSvgIconsSupport());
        $this->theme->setSvgIconsSupport(true);
        self::assertTrue($this->theme->isSvgIconsSupport());
    }

    public function testScreenshotMethods(): void
    {
        self::assertNull($this->theme->getScreenshot());
        $this->theme->setScreenshot('screenshot.png');
        self::assertEquals('screenshot.png', $this->theme->getScreenshot());
    }

    public function testGroupsMethods(): void
    {
        self::assertEmpty($this->theme->getGroups());

        $this->theme->setGroups(['test']);
        self::assertSame(['test'], $this->theme->getGroups());
        self::assertTrue($this->theme->hasGroup('test'));
        self::assertFalse($this->theme->hasGroup('another_test'));
    }

    public function testParentThemeMethods(): void
    {
        self::assertNull($this->theme->getParentTheme());

        $this->theme->setParentTheme('base');
        self::assertEquals('base', $this->theme->getParentTheme());
    }

    public function testDescriptionMethods(): void
    {
        self::assertNull($this->theme->getDescription());

        $this->theme->setDescription('test');
        self::assertEquals('test', $this->theme->getDescription());
    }

    public function testAddPageTemplate(): void
    {
        $pageTemplate = new PageTemplate('Label', 'key', 'route_name');
        $this->theme->addPageTemplate($pageTemplate);

        self::assertEquals(
            new ArrayCollection(['key_route_name' => $pageTemplate]),
            $this->theme->getPageTemplates()
        );
    }

    public function testAddPageTemplateForce(): void
    {
        $pageTemplate = new PageTemplate('Label', 'key', 'route_name');
        $this->theme->addPageTemplate($pageTemplate);
        $newPageTemplate = new PageTemplate('NewLabel', 'key', 'route_name');
        $this->theme->addPageTemplate($newPageTemplate, true);

        self::assertEquals(
            new ArrayCollection(['key_route_name' => $newPageTemplate]),
            $this->theme->getPageTemplates()
        );
    }

    public function testAddPageTemplateAlreadyExists(): void
    {
        $pageTemplate = new PageTemplate('Label', 'key', 'route_name');
        $this->theme->addPageTemplate($pageTemplate);
        $newPageTemplate = new PageTemplate('NewLabel', 'key', 'route_name');
        $newPageTemplate->setDescription('Description');
        $this->theme->addPageTemplate($newPageTemplate);

        self::assertCount(1, $this->theme->getPageTemplates());
        self::assertEquals(
            new ArrayCollection(['key_route_name' => $pageTemplate->setDescription('Description')]),
            $this->theme->getPageTemplates()
        );
    }

    public function testGetPageTemplate(): void
    {
        $pageTemplate = new PageTemplate('Label', 'key', 'route_name');
        $this->theme->addPageTemplate($pageTemplate);

        self::assertEquals($pageTemplate, $this->theme->getPageTemplate('key', 'route_name'));
    }

    public function testGetPageTemplateWithWrongKey(): void
    {
        $pageTemplate = new PageTemplate('Label', 'key', 'route_name');
        $this->theme->addPageTemplate($pageTemplate);

        self::assertFalse($this->theme->getPageTemplate('key1', 'route_name'));
    }

    public function testGetPageTemplateWithWrongRouteName(): void
    {
        $pageTemplate = new PageTemplate('Label', 'key', 'route_name');
        $this->theme->addPageTemplate($pageTemplate);

        self::assertFalse($this->theme->getPageTemplate('key', 'route_name1'));
    }

    public function testGetPageTemplateDisabled(): void
    {
        $pageTemplate = new PageTemplate('Label', 'key', 'route_name');
        $pageTemplate->setEnabled(false);
        $this->theme->addPageTemplate($pageTemplate);

        self::assertFalse($this->theme->getPageTemplate('key', 'route_name'));
    }

    public function testConfigMethods(): void
    {
        $config = [
            'key' => 'value',
        ];

        self::assertEquals([], $this->theme->getConfig());
        $this->theme->setConfig($config);
        self::assertEquals($config, $this->theme->getConfig());
        self::assertEquals($config['key'], $this->theme->getConfigByKey('key'));
        self::assertEquals('default value', $this->theme->getConfigByKey('unknown key', 'default value'));
        $this->theme->setConfigByKey('unknown key', 'unknown value');
        self::assertEquals('unknown value', $this->theme->getConfigByKey('unknown key', 'default value'));
    }

    public function testAddPageTemplateTitle(): void
    {
        $this->theme->addPageTemplateTitle('some_route', 'Some title');
        self::assertEquals('Some title', $this->theme->getPageTemplateTitle('some_route'));
    }

    public function testGetNotExistingPageTemplateTitle(): void
    {
        $this->theme->addPageTemplateTitle('some_route', 'Some title');
        self::assertNull($this->theme->getPageTemplateTitle('not_existing_route'));
    }

    public function testGetPageTemplateTitles(): void
    {
        $expected = [
            'some_route' => 'Some route',
            'some_other_route' => 'Some other route',
        ];

        $this->theme->addPageTemplateTitle('some_route', 'Some route');
        $this->theme->addPageTemplateTitle('some_other_route', 'Some other route');
        $this->theme->addPageTemplateTitle('some_other_route', 'Some other route');
        self::assertEquals($expected, $this->theme->getPageTemplateTitles());
    }

    public function testGetFonts(): void
    {
        self::assertSame([], $this->theme->getFonts());
    }

    public function testSetFonts(): void
    {
        $this->theme->setFonts(['test' => 'fonts']);
        self::assertSame(['test' => 'fonts'], $this->theme->getFonts());

        $this->theme->setFonts([]);
        self::assertSame([], $this->theme->getFonts());
    }
}
