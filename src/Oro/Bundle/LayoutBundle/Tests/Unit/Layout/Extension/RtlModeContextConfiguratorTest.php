<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\RtlModeContextConfigurator;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContext;

class RtlModeContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $themeManager;

    /** @var LocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationProvider;

    /** @var RtlModeContextConfigurator */
    private $contextConfigurator;

    protected function setUp(): void
    {
        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->localizationProvider = $this->createMock(LocalizationProviderInterface::class);

        $this->contextConfigurator = new RtlModeContextConfigurator($this->themeManager, $this->localizationProvider);
    }

    public function testConfigureContextWhenNoThemeName(): void
    {
        $this->themeManager->expects(self::never())
            ->method(self::anything());

        $this->localizationProvider->expects(self::never())
            ->method(self::anything());

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);

        $context->resolve();
        self::assertFalse($context->get('is_rtl_mode_enabled'));
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

        $this->localizationProvider->expects(self::never())
            ->method(self::anything());

        $context = new LayoutContext();
        $context->getResolver()->setRequired('theme');
        $context->set('theme', $themeName);

        $this->contextConfigurator->configureContext($context);

        $context->resolve();
        self::assertFalse($context->get('is_rtl_mode_enabled'));
    }

    public function testConfigureContextWhenNoLocalization(): void
    {
        $themeName = 'test';

        $theme = new Theme($themeName);
        $theme->setRtlSupport(true);

        $this->themeManager->expects(self::any())
            ->method('hasTheme')
            ->with($themeName)
            ->willReturn(true);
        $this->themeManager->expects(self::any())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $this->localizationProvider->expects(self::any())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $context = new LayoutContext();
        $context->getResolver()->setRequired('theme');
        $context->set('theme', $themeName);

        $this->contextConfigurator->configureContext($context);

        $context->resolve();
        self::assertFalse($context->get('is_rtl_mode_enabled'));
    }

    /**
     * @dataProvider stylesOutputDataProvider
     */
    public function testConfigureContext(bool $themeRtl, bool $localRtl, bool $expected): void
    {
        $themeName = 'test';

        $theme = new Theme($themeName);
        $theme->setRtlSupport($themeRtl);

        $this->themeManager->expects(self::any())
            ->method('hasTheme')
            ->with($themeName)
            ->willReturn(true);
        $this->themeManager->expects(self::any())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $localization = new Localization();
        $localization->setRtlMode($localRtl);

        $this->localizationProvider->expects(self::any())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $context = new LayoutContext();
        $context->getResolver()->setRequired('theme');
        $context->set('theme', $themeName);

        $this->contextConfigurator->configureContext($context);

        $context->resolve();
        self::assertSame($expected, $context->get('is_rtl_mode_enabled'));
    }

    public function stylesOutputDataProvider(): array
    {
        return [
            [
                'themeRtl' => false,
                'localizationRtl' => false,
                'expected' => false,
            ],
            [
                'themeRtl' => true,
                'localizationRtl' => false,
                'expected' => false,
            ],
            [
                'themeRtl' => false,
                'localizationRtl' => true,
                'expected' => false,
            ],
            [
                'themeRtl' => true,
                'localizationRtl' => true,
                'expected' => true,
            ],
        ];
    }
}
