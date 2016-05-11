<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Strategy;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Strategy\DefaultTranslationStrategy;

class DefaultTranslationStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocaleSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeSettings;

    /**
     * @var DefaultTranslationStrategy
     */
    protected $strategy;

    public function setUp()
    {
        $this->localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        $this->strategy = new DefaultTranslationStrategy($this->localeSettings, '2016-05-10T14:57:01+00:00');
    }

    public function testGetName()
    {
        $this->assertEquals(DefaultTranslationStrategy::NAME, $this->strategy->getName());
    }

    public function testGetLocaleFallbacks()
    {
        $currentLocale = 'fr';

        $this->localeSettings->expects($this->once())
            ->method('getLocale')
            ->willReturn($currentLocale);

        $this->assertEquals(
            [
                Configuration::DEFAULT_LOCALE => [
                    $currentLocale => [],
                ],
            ],
            $this->strategy->getLocaleFallbacks()
        );
    }

    public function testGetLocaleFallbacksNotInstalledApp()
    {
        $this->strategy = new DefaultTranslationStrategy($this->localeSettings, null);

        $this->assertEquals(
            [
                Configuration::DEFAULT_LOCALE => [],
            ],
            $this->strategy->getLocaleFallbacks()
        );
    }
}
