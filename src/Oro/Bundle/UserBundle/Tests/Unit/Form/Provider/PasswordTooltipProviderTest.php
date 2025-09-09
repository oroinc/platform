<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\UserBundle\Form\Provider\PasswordTooltipProvider;
use Oro\Bundle\UserBundle\Provider\PasswordComplexityConfigProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordTooltipProviderTest extends TestCase
{
    private TranslatorInterface $translator;

    #[\Override]
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
    public function testGetTooltip(array $configMap, $expected): void
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

    private function getConfigProvider(
        array $configMap,
        bool $loginFormFeatureEnabled = true
    ): PasswordComplexityConfigProvider {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::any())
            ->method('get')
            ->willReturnMap($configMap);

        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn($loginFormFeatureEnabled);

        return new PasswordComplexityConfigProvider($configManager, $featureChecker);
    }
}
