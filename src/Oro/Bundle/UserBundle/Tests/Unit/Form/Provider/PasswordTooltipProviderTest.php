<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Provider;

use Oro\Bundle\UserBundle\Validator\PasswordComplexityValidator;
use Symfony\Component\Translation\TranslatorInterface;

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
        $configManager = $this->getConfigManager($configMap);
        $provider = new PasswordTooltipProvider($configManager, $this->translator);

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
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'expected' => PasswordTooltipProvider::TOOLTIP_PREFIX,
            ],
            'min length rule' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 2],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'expected' => PasswordTooltipProvider::TOOLTIP_PREFIX .
                              PasswordTooltipProvider::TOOLTIP_MIN_LENGTH,

            ],
            'some rules' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'expected' => PasswordTooltipProvider::TOOLTIP_PREFIX .
                              PasswordTooltipProvider::TOOLTIP_UPPER_CASE .
                              PasswordTooltipProvider::TOOLTIP_SEPARATOR .
                              PasswordTooltipProvider::TOOLTIP_SPECIAL_CHARS,

            ],
            'all rules' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 2],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'expected' => PasswordTooltipProvider::TOOLTIP_PREFIX .
                              PasswordTooltipProvider::TOOLTIP_MIN_LENGTH .
                              PasswordTooltipProvider::TOOLTIP_SEPARATOR .
                              PasswordTooltipProvider::TOOLTIP_UPPER_CASE .
                              PasswordTooltipProvider::TOOLTIP_SEPARATOR .
                              PasswordTooltipProvider::TOOLTIP_NUMBERS .
                              PasswordTooltipProvider::TOOLTIP_SEPARATOR .
                              PasswordTooltipProvider::TOOLTIP_SPECIAL_CHARS,
            ],
        ];
    }

    /**
     * @param array $configMap
     *
     * @return ConfigManager
     */
    protected function getConfigManager(array $configMap)
    {
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->method('get')->willReturnMap($configMap);

        /** @var ConfigManager $configManager */
        return $configManager;
    }

    protected function tearDown()
    {
        unset($this->translator);
    }
}
