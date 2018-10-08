<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Strategy;

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

    public function setUp()
    {
        $this->languageProvider = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Provider\LanguageProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->strategy = new DefaultTranslationStrategy($this->languageProvider, '2016-05-10T14:57:01+00:00');
    }

    public function testGetName()
    {
        $this->assertEquals(DefaultTranslationStrategy::NAME, $this->strategy->getName());
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->strategy->isApplicable());
    }

    public function testGetLocaleFallbacks()
    {
        $currentLanguages = [
            'fr' => 'French',
            'ua' => 'Ukrainian',
        ];

        $this->languageProvider->expects($this->once())
            ->method('getAvailableLanguages')
            ->willReturn($currentLanguages);

        $this->assertEquals(
            [
                Configuration::DEFAULT_LOCALE => [
                    'fr' => [],
                    'ua' => [],
                ],
            ],
            $this->strategy->getLocaleFallbacks()
        );
    }

    public function testGetLocaleFallbacksNotInstalledApp()
    {
        $this->strategy = new DefaultTranslationStrategy($this->languageProvider, null);

        $this->assertEquals(
            [
                Configuration::DEFAULT_LOCALE => [],
            ],
            $this->strategy->getLocaleFallbacks()
        );
    }
}
