<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Form\Provider\PasswordTooltipProvider;
use Oro\Bundle\UserBundle\Provider\PasswordComplexityConfigProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordTooltipProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface */
    private $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);
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

    public function getTooltipDataProvider(): array
    {
        return [
            'no rules enabled' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'expected' => PasswordTooltipProvider::UNRESTRICTED,
            ],
            'min length rule' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 2],
                ],
                'expected' => PasswordTooltipProvider::BASE .
                              PasswordTooltipProvider::MIN_LENGTH,

            ],
            'some rules' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, true],
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
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'expected' => PasswordTooltipProvider::BASE .
                              PasswordTooltipProvider::MIN_LENGTH .
                              PasswordTooltipProvider::SEPARATOR .
                              PasswordTooltipProvider::LOWER_CASE .
                              PasswordTooltipProvider::SEPARATOR .
                              PasswordTooltipProvider::UPPER_CASE .
                              PasswordTooltipProvider::SEPARATOR .
                              PasswordTooltipProvider::NUMBERS .
                              PasswordTooltipProvider::SEPARATOR .
                              PasswordTooltipProvider::SPECIAL_CHARS,
            ],
        ];
    }

    private function getConfigProvider(array $configMap): PasswordComplexityConfigProvider
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('get')
            ->willReturnMap($configMap);

        return new PasswordComplexityConfigProvider($configManager);
    }
}
