<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Model;

use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\OldThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OldThemeProviderTest extends TestCase
{
    private CurrentThemeProvider&MockObject $currentThemeProvider;
    private ThemeManager&MockObject $themeManager;

    private OldThemeProvider $oldThemeProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->currentThemeProvider = $this->createMock(CurrentThemeProvider::class);
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->oldThemeProvider = new OldThemeProvider($this->currentThemeProvider, $this->themeManager);
    }

    /**
     * @dataProvider oldThemeDataProvider
     */
    public function testIsOldTheme(array $parentThemes, mixed $currentTheme, bool $expected): void
    {
        $this->currentThemeProvider
            ->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn($currentTheme);

        if ($currentTheme === null) {
            $currentTheme = '';
        }

        $this->themeManager
            ->expects(self::once())
            ->method('themeHasParent')
            ->with($currentTheme, $parentThemes)
            ->willReturn($expected);

        self::assertSame($expected, $this->oldThemeProvider->isOldTheme($parentThemes));
    }

    public function oldThemeDataProvider(): array
    {
        return [
            'null current theme' => [
                'parentThemes' => ['oldTheme'],
                'currentTheme' => null,
                'expected' => false
            ],
            'old theme' => [
                'parentThemes' => ['oldTheme'],
                'currentTheme' => 'oldTheme',
                'expected' => true
            ],
            'new theme' => [
                'parentThemes' => ['oldTheme'],
                'currentTheme' => 'newTheme',
                'expected' => false
            ],
        ];
    }
}
