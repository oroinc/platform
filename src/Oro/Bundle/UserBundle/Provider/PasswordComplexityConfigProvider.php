<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Generates a tooltip text for the system configured password complexity requirements
 */
class PasswordComplexityConfigProvider
{
    public const CONFIG_MIN_LENGTH = 'oro_user.password_min_length';
    public const CONFIG_NUMBERS = 'oro_user.password_numbers';
    public const CONFIG_LOWER_CASE = 'oro_user.password_lower_case';
    public const CONFIG_UPPER_CASE = 'oro_user.password_upper_case';
    public const CONFIG_SPECIAL_CHARS = 'oro_user.password_special_chars';

    /**
     * @var array Map of password config keys and type
     */
    public static $configKeys = [
        self::CONFIG_MIN_LENGTH => 'int',
        self::CONFIG_NUMBERS => 'bool',
        self::CONFIG_LOWER_CASE => 'bool',
        self::CONFIG_UPPER_CASE => 'bool',
        self::CONFIG_SPECIAL_CHARS => 'bool',
    ];

    public function __construct(
        private ConfigManager $configManager,
        private FeatureChecker $featureChecker
    ) {
    }

    /**
     * Return a map of configured rules with typecasted values
     */
    public function getAllRules(): array
    {
        $parts = [];

        if (!$this->featureChecker->isFeatureEnabled('user_login_password')) {
            return $parts;
        }

        foreach (self::$configKeys as $configKey => $type) {
            $value = $this->configManager->get($configKey);
            settype($value, $type);
            $parts[$configKey] = $value;
        }

        return $parts;
    }

    /**
     * Get the min length requirement for passwords
     */
    public function getMinLength(): int
    {
        return $this->featureChecker->isFeatureEnabled('user_login_password')
            ? (int) $this->configManager->get(self::CONFIG_MIN_LENGTH)
            : 0;
    }

    /**
     * Get the lower case requirement for passwords
     */
    public function getLowerCase(): bool
    {
        return $this->featureChecker->isFeatureEnabled('user_login_password')
            && $this->configManager->get(self::CONFIG_LOWER_CASE);
    }

    /**
     * Get the upper case requirement for passwords
     */
    public function getUpperCase(): bool
    {
        return $this->featureChecker->isFeatureEnabled('user_login_password')
            && $this->configManager->get(self::CONFIG_UPPER_CASE);
    }

    /**
     * Get the upper case requirement for passwords
     */
    public function getNumbers(): bool
    {
        return $this->featureChecker->isFeatureEnabled('user_login_password')
            && $this->configManager->get(self::CONFIG_NUMBERS);
    }

    /**
     * Get the upper case requirement for passwords
     */
    public function getSpecialChars(): bool
    {
        return $this->featureChecker->isFeatureEnabled('user_login_password')
            && $this->configManager->get(self::CONFIG_SPECIAL_CHARS);
    }
}
