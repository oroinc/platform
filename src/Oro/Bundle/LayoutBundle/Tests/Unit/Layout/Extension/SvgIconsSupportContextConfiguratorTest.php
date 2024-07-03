<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\SvgIconsSupportContextConfigurator;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SvgIconsSupportContextConfiguratorTest extends TestCase
{
    private ThemeManager|MockObject $themeManager;

    private SvgIconsSupportContextConfigurator $contextConfigurator;

    protected function setUp(): void
    {
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->contextConfigurator = new SvgIconsSupportContextConfigurator($this->themeManager);
    }

    public function testConfigureContextWhenNoThemeName(): void
    {
        $this->themeManager->expects(self::never())
            ->method(self::anything());

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);

        $context->resolve();

        self::assertFalse($context->get('is_svg_icons_support'));
    }

    public function testConfigureContextWhenNoTheme(): void
    {
        $themeName = 'test';

        $this->themeManager->expects(self::any())
            ->method('hasTheme')
            ->with($themeName)
            ->willReturn(false);
        $this->themeManager->expects(self::never())
            ->method('getTheme');

        $context = new LayoutContext();
        $context->getResolver()->setRequired('theme');
        $context->set('theme', $themeName);

        $this->contextConfigurator->configureContext($context);

        $context->resolve();

        self::assertFalse($context->get('is_svg_icons_support'));
    }

    /**
     * @dataProvider getConfigureContextDataProvider
     */
    public function testConfigureContext(bool $themeSvgIconsSupport): void
    {
        $themeName = 'test';

        $theme = new Theme($themeName);
        $theme->setSvgIconsSupport($themeSvgIconsSupport);

        $this->themeManager->expects(self::any())
            ->method('hasTheme')
            ->with($themeName)
            ->willReturn(true);
        $this->themeManager->expects(self::any())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $context = new LayoutContext();
        $context->getResolver()->setRequired('theme');
        $context->set('theme', $themeName);

        $this->contextConfigurator->configureContext($context);

        $context->resolve();

        self::assertSame($themeSvgIconsSupport, $context->get('is_svg_icons_support'));
    }

    public function getConfigureContextDataProvider(): array
    {
        return [
            [false],
            [true],
        ];
    }
}
