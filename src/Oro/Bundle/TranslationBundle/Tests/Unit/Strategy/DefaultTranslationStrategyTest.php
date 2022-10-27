<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Strategy;

use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy;
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

    /**
     * @var LocalizationFallbackStrategy
     */
    protected $fallbackStrategy;

    protected function setUp(): void
    {
        $this->languageProvider = $this->getMockBuilder(LanguageProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fallbackStrategy = $this->getMockBuilder(LocalizationFallbackStrategy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->strategy = new DefaultTranslationStrategy($this->languageProvider, '2016-05-10T14:57:01+00:00');
        $this->strategy->setStrategy($this->fallbackStrategy);
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
        $localeFallbacks = [
            Configuration::DEFAULT_LOCALE => [
                'fr_FR' => [],
                'uk_UA' => [],
            ],
        ];

        $this->fallbackStrategy->expects(self::once())
            ->method('getLocaleFallbacks')
            ->willReturn($localeFallbacks);

        static::assertEquals($localeFallbacks, $this->strategy->getLocaleFallbacks());
    }

    public function testGetLocaleFallbacksNotInstalledApp()
    {
        $this->strategy = new DefaultTranslationStrategy($this->languageProvider, null);
        $this->strategy->setStrategy($this->fallbackStrategy);

        $this->assertEquals(
            [
                Configuration::DEFAULT_LOCALE => [],
            ],
            $this->strategy->getLocaleFallbacks()
        );
    }
}
