<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Generates a tooltip text for the system configured password complexity requirements
 */
class PasswordComplexityConfigProvider
{
    const CONFIG_MIN_LENGTH = 'oro_user.password_min_length';
    const CONFIG_NUMBERS = 'oro_user.password_numbers';
    const CONFIG_LOWER_CASE = 'oro_user.password_lower_case';
    const CONFIG_UPPER_CASE = 'oro_user.password_upper_case';
    const CONFIG_SPECIAL_CHARS = 'oro_user.password_special_chars';

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

    /** @var ConfigManager */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Return a map of configured rules with typecasted values
     *
     * @return array
     */
    public function getAllRules()
    {
        $parts = [];

        foreach (self::$configKeys as $configKey => $type) {
            $value = $this->configManager->get($configKey);
            settype($value, $type);
            $parts[$configKey] = $value;
        }

        return $parts;
    }

    /**
     * Get the min length requirement for passwords
     *
     * @return int
     */
    public function getMinLength()
    {
        return (int) $this->configManager->get(self::CONFIG_MIN_LENGTH);
    }

    /**
     * Get the lower case requirement for passwords
     *
     * @return bool
     */
    public function getLowerCase()
    {
        return (bool) $this->configManager->get(self::CONFIG_LOWER_CASE);
    }

    /**
     * Get the upper case requirement for passwords
     *
     * @return bool
     */
    public function getUpperCase()
    {
        return (bool) $this->configManager->get(self::CONFIG_UPPER_CASE);
    }

    /**
     * Get the upper case requirement for passwords
     *
     * @return bool
     */
    public function getNumbers()
    {
        return (bool) $this->configManager->get(self::CONFIG_NUMBERS);
    }

    /**
     * Get the upper case requirement for passwords
     *
     * @return bool
     */
    public function getSpecialChars()
    {
        return (bool) $this->configManager->get(self::CONFIG_SPECIAL_CHARS);
    }
}
