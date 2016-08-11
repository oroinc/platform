<?php

namespace Oro\Bundle\TranslationBundle\tests\Unit\Helper;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Helper\LanguageHelper;

use Oro\Component\Testing\Unit\EntityTrait;

class LanguageHelperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var LanguageHelper */
    protected $helper;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();

        $this->helper = new LanguageHelper($this->configManager);
    }

    protected function tearDown()
    {
        unset($this->helper, $this->configManager);
    }

    /**
     * @dataProvider updateSystemConfigurationDataProvider
     * @param Language $language
     * @param array $currentConfig
     * @param array $expected
     */
    public function testUpdateSystemConfiguration(Language $language, array $currentConfig, array $expected)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_locale.languages', [])
            ->willReturn($currentConfig);

        $actual = null;

        $this->configManager->expects($this->once())
            ->method('set')
            ->willReturnCallback(
                function ($name, $value) use (&$actual) {
                    $actual = $value;
                }
            );

        $this->configManager->expects($this->once())->method('flush');

        $this->helper->updateSystemConfiguration($language);

        $this->assertEquals($expected, array_values($actual));
    }

    /**
     * @return array
     */
    public function updateSystemConfigurationDataProvider()
    {
        return [
            [
                'language' => $this->getLanguage('en', true),
                'currentConfig' => ['fr_FR'],
                'expected' => ['fr_FR', 'en']
            ],
            [
                'language' => $this->getLanguage('en', true),
                'currentConfig' => ['en', 'fr_FR'],
                'expected' => ['en', 'fr_FR']
            ],
            [
                'language' => $this->getLanguage('fr_FR', false),
                'currentConfig' => ['en', 'fr_FR', 'js_JP'],
                'expected' => ['en', 'js_JP']
            ],
            [
                'language' => $this->getLanguage('pl_PL', false),
                'currentConfig' => ['en', 'fr_FR', 'js_JP'],
                'expected' => ['en', 'fr_FR', 'js_JP']
            ]
        ];
    }

    /**
     * @param string $code
     * @param bool $enabled
     * @return Language
     */
    protected function getLanguage($code, $enabled)
    {
        return $this->getEntity(Language::class, ['id' => mt_rand(1, 1000), 'code' => $code, 'enabled' => $enabled]);
    }
}
