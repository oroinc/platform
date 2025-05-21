<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\SvgIconsSupportContextConfigurator;
use Oro\Bundle\LayoutBundle\Provider\SvgIconsSupportProvider;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SvgIconsSupportContextConfiguratorTest extends TestCase
{
    private SvgIconsSupportProvider&MockObject $svgIconsSupportProvider;
    private ThemeManager&MockObject $themeManager;

    private SvgIconsSupportContextConfigurator $contextConfigurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->svgIconsSupportProvider = $this->createMock(SvgIconsSupportProvider::class);
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->contextConfigurator = new SvgIconsSupportContextConfigurator($this->svgIconsSupportProvider);
        $this->contextConfigurator->setThemeManager($this->themeManager);
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

    /**
     * @dataProvider getConfigureContextDataProvider
     */
    public function testConfigureContext(bool $themeSvgIconsSupport): void
    {
        $themeName = 'test';

        $this->themeManager
            ->expects(self::once())
            ->method('getThemeOption')
            ->with($themeName, 'svg_icons_support')
            ->willReturn($themeSvgIconsSupport);

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
