<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Strategy;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy;
use Oro\Bundle\TranslationBundle\Strategy\DefaultTranslationStrategy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultTranslationStrategyTest extends TestCase
{
    private ApplicationState&MockObject $applicationState;
    private DefaultTranslationStrategy $strategy;
    private LocalizationFallbackStrategy $fallbackStrategy;

    #[\Override]
    protected function setUp(): void
    {
        $this->applicationState = $this->createMock(ApplicationState::class);
        $this->fallbackStrategy = $this->createMock(LocalizationFallbackStrategy::class);

        $this->strategy = new DefaultTranslationStrategy($this->fallbackStrategy, $this->applicationState);
    }

    public function testGetName(): void
    {
        $this->assertEquals('default', $this->strategy->getName());
    }

    public function testIsApplicable(): void
    {
        $this->assertTrue($this->strategy->isApplicable());
    }

    public function testGetLocaleFallbacks(): void
    {
        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $this->strategy = new DefaultTranslationStrategy($this->fallbackStrategy, $this->applicationState);

        $localeFallbacks = [
            Configuration::DEFAULT_LOCALE => [
                'fr_FR' => [],
                'uk_UA' => [],
            ],
        ];

        $this->fallbackStrategy->expects(self::once())
            ->method('getLocaleFallbacks')
            ->willReturn($localeFallbacks);

        self::assertEquals($localeFallbacks, $this->strategy->getLocaleFallbacks());
    }

    public function testGetLocaleFallbacksNotInstalledApp(): void
    {
        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(false);

        $strategy = new DefaultTranslationStrategy($this->fallbackStrategy, $this->applicationState);

        $this->assertEquals(
            [
                Configuration::DEFAULT_LOCALE => [],
            ],
            $strategy->getLocaleFallbacks()
        );
    }
}
