<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Strategy;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\TranslationBundle\Strategy\DefaultTranslationStrategy;

class DefaultTranslationStrategyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LanguageProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $languageProvider;

    /**
     * @var DefaultTranslationStrategy
     */
    protected $strategy;

    private ApplicationState $applicationState;

    protected function setUp(): void
    {
        $this->languageProvider = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Provider\LanguageProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->applicationState = $this->createMock(ApplicationState::class);
        $this->strategy = new DefaultTranslationStrategy($this->languageProvider, $this->applicationState);
    }

    public function testGetName()
    {
        $this->assertEquals(DefaultTranslationStrategy::NAME, $this->strategy->getName());
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->strategy->isApplicable());
    }

    public function testGetLocaleFallbacks(): void
    {
        $this->applicationState->method('isInstalled')->willReturn(true);

        $this->strategy = new DefaultTranslationStrategy($this->languageProvider, $this->applicationState);

        $currentLanguagesCodes = ['fr_FR', 'uk_UA',];

        $this->languageProvider->expects(static::once())
            ->method('getAvailableLanguageCodes')
            ->willReturn($currentLanguagesCodes);

        static::assertEquals(
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
        $this->applicationState->method('isInstalled')->willReturn(false);

        $strategy = new DefaultTranslationStrategy($this->languageProvider, $this->applicationState);

        $this->assertEquals(
            [
                Configuration::DEFAULT_LOCALE => [],
            ],
            $strategy->getLocaleFallbacks()
        );
    }
}
