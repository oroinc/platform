<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Strategy;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\TranslationBundle\Strategy\DefaultTranslationStrategy;

class DefaultTranslationStrategyTest extends \PHPUnit\Framework\TestCase
{
    /** @var LanguageProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $languageProvider;

    /** @var ApplicationState|\PHPUnit\Framework\MockObject\MockObject */
    private $applicationState;

    /** @var DefaultTranslationStrategy */
    private $strategy;

    protected function setUp(): void
    {
        $this->languageProvider = $this->createMock(LanguageProvider::class);
        $this->applicationState = $this->createMock(ApplicationState::class);

        $this->strategy = new DefaultTranslationStrategy($this->languageProvider, $this->applicationState);
    }

    public function testGetName()
    {
        $this->assertEquals('default', $this->strategy->getName());
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->strategy->isApplicable());
    }

    public function testGetLocaleFallbacks(): void
    {
        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $this->strategy = new DefaultTranslationStrategy($this->languageProvider, $this->applicationState);

        $currentLanguagesCodes = ['fr_FR', 'uk_UA',];

        $this->languageProvider->expects(self::once())
            ->method('getAvailableLanguageCodes')
            ->willReturn($currentLanguagesCodes);

        self::assertEquals(
            [
                Configuration::DEFAULT_LOCALE => [
                    'fr_FR' => [],
                    'uk_UA' => [],
                ],
            ],
            $this->strategy->getLocaleFallbacks()
        );
    }

    public function testGetLocaleFallbacksNotInstalledApp()
    {
        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(false);

        $strategy = new DefaultTranslationStrategy($this->languageProvider, $this->applicationState);

        $this->assertEquals(
            [
                Configuration::DEFAULT_LOCALE => [],
            ],
            $strategy->getLocaleFallbacks()
        );
    }
}
