<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Api;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Api\UserPredefinedLanguageCodeResolver;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserPredefinedLanguageCodeResolverTest extends TestCase
{
    private LocalizationManager&MockObject $localizationManager;
    private ConfigManager&MockObject $configManager;
    private UserPredefinedLanguageCodeResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->localizationManager = $this->createMock(LocalizationManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->resolver = new UserPredefinedLanguageCodeResolver(
            $this->localizationManager,
            $this->configManager
        );
    }

    public function testDescription(): void
    {
        self::assertEquals(
            '**user** for a default language for the current user.',
            $this->resolver->getDescription()
        );
    }

    public function testResolveWhenNoDefaultLocalization(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_locale.default_localization')
            ->willReturn(null);
        $this->localizationManager->expects(self::once())
            ->method('getLocalizationData')
            ->with(self::identicalTo(0))
            ->willReturn([]);

        self::assertEquals('en', $this->resolver->resolve());
    }

    public function testResolveWhenDefaultLocalizationExists(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_locale.default_localization')
            ->willReturn(123);
        $this->localizationManager->expects(self::once())
            ->method('getLocalizationData')
            ->with(123)
            ->willReturn(['languageCode' => 'en_CA']);

        self::assertEquals('en_CA', $this->resolver->resolve());
    }
}
