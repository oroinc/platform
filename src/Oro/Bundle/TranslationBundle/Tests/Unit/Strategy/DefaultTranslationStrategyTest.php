<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Strategy;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Strategy\DefaultTranslationStrategy;
use Oro\Bundle\TranslationBundle\Translation\TranslationStatusInterface;

class DefaultTranslationStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cm;

    /**
     * @var DefaultTranslationStrategy
     */
    protected $strategy;

    public function setUp()
    {
        $this->cm = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->strategy = new DefaultTranslationStrategy($this->cm, '2016-05-10T14:57:01+00:00');
    }

    public function testGetName()
    {
        $this->assertEquals(DefaultTranslationStrategy::NAME, $this->strategy->getName());
    }

    public function testGetLocaleFallbacks()
    {
        $currentLanguages = ['fr' => 3, 'ua' => 3];

        $this->cm->expects($this->once())
            ->method('get')
            ->with(TranslationStatusInterface::CONFIG_KEY)
            ->willReturn($currentLanguages);

        $this->assertEquals(
            [
                'en' => [
                    'fr' => [],
                    'ua' => [],
                ],
            ],
            $this->strategy->getLocaleFallbacks()
        );
    }

    public function testGetLocaleFallbacksNotInstalledApp()
    {
        $this->strategy = new DefaultTranslationStrategy($this->cm, null);

        $this->assertEquals(
            [
                Configuration::DEFAULT_LOCALE => [],
            ],
            $this->strategy->getLocaleFallbacks()
        );
    }
}
