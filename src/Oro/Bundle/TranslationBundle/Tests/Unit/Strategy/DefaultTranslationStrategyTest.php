<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Strategy;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
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

        $this->strategy = new DefaultTranslationStrategy($this->localeSettings);
    }

    public function testGetCurrentLocale()
    {
        $currentLocale = 'en';

        $this->localeSettings->expects($this->once())
            ->method('getLocale')
            ->willReturn($currentLocale);

        $this->assertEquals($currentLocale, $this->strategy->getCurrentLocale());
    }

    public function testGetLocaleFallbacks()
    {
        $currentLocale = 'en';

        $this->localeSettings->expects($this->once())
            ->method('getLocale')
            ->willReturn($currentLocale);

        $this->assertEquals([$currentLocale => []], $this->strategy->getLocaleFallbacks());
    }
}
