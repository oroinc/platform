<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\UserBundle\Provider\PasswordComplexityConfigProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Form\Provider\PasswordTooltipProvider;

class PasswordTooltipProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatorInterface */
    protected $translator;

    protected function setUp()
    {
        $this->translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator->method('trans')->willReturnArgument(0);
    }

    /**
     * @dataProvider getTooltipDataProvider
     *
     * @param array $configMap System config
     * @param string $expected Expected resulting tooltip
     */
    public function testGetTooltip(array $configMap, $expected)
    {
        $configProvider = $this->getConfigProvider($configMap);
        $provider = new PasswordTooltipProvider($configProvider, $this->translator);

        $this->assertEquals($expected, $provider->getTooltip());
    }

    /**
     * @return array
     */
    public function getTooltipDataProvider()
    {
        return [
            'no rules enabled' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'expected' => PasswordTooltipProvider::UNRESTRICTED,
            ],
            'min length rule' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 2],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'expected' => PasswordTooltipProvider::BASE .
                              PasswordTooltipProvider::MIN_LENGTH,

            ],
            'some rules' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'expected' => PasswordTooltipProvider::BASE .
                              PasswordTooltipProvider::UPPER_CASE .
                              PasswordTooltipProvider::SEPARATOR .
                              PasswordTooltipProvider::SPECIAL_CHARS,

            ],
            'all rules' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 2],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'expected' => PasswordTooltipProvider::BASE .
                              PasswordTooltipProvider::MIN_LENGTH .
                              PasswordTooltipProvider::SEPARATOR .
                              PasswordTooltipProvider::UPPER_CASE .
                              PasswordTooltipProvider::SEPARATOR .
                              PasswordTooltipProvider::NUMBERS .
                              PasswordTooltipProvider::SEPARATOR .
                              PasswordTooltipProvider::SPECIAL_CHARS,
            ],
        ];
    }

    /**
     * @param array $configMap
     *
     * @return PasswordComplexityConfigProvider
     */
    protected function getConfigProvider(array $configMap)
    {
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->method('get')->willReturnMap($configMap);

        /** @var ConfigManager $configManager */
        $configProvider = new PasswordComplexityConfigProvider($configManager);

        /** @var PasswordComplexityConfigProvider $configManager */
        return $configProvider;
    }

    protected function tearDown()
    {
        unset($this->translator);
    }
}
