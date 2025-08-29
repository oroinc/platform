<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\UserBundle\Provider\PasswordComplexityConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PasswordComplexityConfigProviderTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private FeatureChecker&MockObject $featureChecker;
    private PasswordComplexityConfigProvider $configProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->configProvider = new PasswordComplexityConfigProvider($this->configManager);
        $this->configProvider->setFeatureChecker($this->featureChecker);
    }

    public function testGetAllRulesOnDisabledFeature(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn(false);

        $this->configManager->expects(self::never())
            ->method('get');

        self::assertEquals([], $this->configProvider->getAllRules());
    }

    public function testGetAllRulesOnEnabledFeature(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn(true);

        $this->configManager->expects(self::exactly(5))
            ->method('get')
            ->willReturnMap([
                ['oro_user.password_min_length', false, false, null, 2],
                ['oro_user.password_numbers', false, false, null, true],
                ['oro_user.password_lower_case', false, false, null, true],
                ['oro_user.password_upper_case', false, false, null, false],
                ['oro_user.password_special_chars', false, false, null, true],
            ]);

        self::assertEquals(
            [
                'oro_user.password_min_length' => 2,
                'oro_user.password_numbers' => true,
                'oro_user.password_lower_case' => true,
                'oro_user.password_upper_case' => false,
                'oro_user.password_special_chars' => true,
            ],
            $this->configProvider->getAllRules()
        );
    }

    public function testGetMinLengthOnDisabledFeature(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn(false);

        $this->configManager->expects(self::never())
            ->method('get');

        self::assertEquals(0, $this->configProvider->getMinLength());
    }

    public function testGetMinLengthOnEnabledFeature(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn(true);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_user.password_min_length')
            ->willReturn(2);

        self::assertEquals(2, $this->configProvider->getMinLength());
    }

    public function testGetGetLowerCaseOnDisabledFeature(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn(false);

        $this->configManager->expects(self::never())
            ->method('get');

        self::assertEquals(0, $this->configProvider->getLowerCase());
    }

    public function testGetGetLowerCaseOnEnabledFeature(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn(true);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_user.password_lower_case')
            ->willReturn(true);

        self::assertEquals(true, $this->configProvider->getLowerCase());
    }

    public function testGetGetUpperCaseOnDisabledFeature(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn(false);

        $this->configManager->expects(self::never())
            ->method('get');

        self::assertEquals(0, $this->configProvider->getUpperCase());
    }

    public function testGetGetUpperCaseOnEnabledFeature(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn(true);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_user.password_upper_case')
            ->willReturn(true);

        self::assertEquals(true, $this->configProvider->getUpperCase());
    }

    public function testGetGetNumbersOnDisabledFeature(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn(false);

        $this->configManager->expects(self::never())
            ->method('get');

        self::assertEquals(0, $this->configProvider->getNumbers());
    }

    public function testGetNumbersCaseOnEnabledFeature(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn(true);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_user.password_numbers')
            ->willReturn(true);

        self::assertEquals(true, $this->configProvider->getNumbers());
    }

    public function testGetSpecialCharsOnDisabledFeature(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn(false);

        $this->configManager->expects(self::never())
            ->method('get');

        self::assertEquals(0, $this->configProvider->getSpecialChars());
    }

    public function testGetSpecialCharsOnEnabledFeature(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn(true);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_user.password_special_chars')
            ->willReturn(true);

        self::assertEquals(true, $this->configProvider->getSpecialChars());
    }
}
