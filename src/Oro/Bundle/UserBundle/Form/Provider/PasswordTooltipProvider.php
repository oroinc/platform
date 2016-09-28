<?php

namespace Oro\Bundle\UserBundle\Form\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Validator\PasswordComplexityValidator;

/**
 * Generates a tooltip text for the system configured password complexity requirements
 */
class PasswordTooltipProvider
{
    const BASE = 'oro.user.password_complexity.';
    const UNRESTRICTED = 'oro.user.password_complexity.unrestricted';
    const MIN_LENGTH = 'min_length';
    const UPPER_CASE = 'upper_case';
    const NUMBERS = 'numbers';
    const SPECIAL_CHARS = 'special_chars';
    const SEPARATOR = '_';

    /**
     * @var array Map of the config keys and their corresponding tooltip translation keys
     */
    public static $tooltipPartsMap = [
        PasswordComplexityValidator::CONFIG_MIN_LENGTH => self::MIN_LENGTH,
        PasswordComplexityValidator::CONFIG_UPPER_CASE => self::UPPER_CASE,
        PasswordComplexityValidator::CONFIG_NUMBERS => self::NUMBERS,
        PasswordComplexityValidator::CONFIG_SPECIAL_CHARS => self::SPECIAL_CHARS,
    ];

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(ConfigManager $configManager, TranslatorInterface $translator)
    {
        $this->configManager = $configManager;
        $this->translator = $translator;
    }

    /**
     * Build the tooltip text based on system configuration for password complexity
     *
     * @return string
     */
    public function getTooltip()
    {
        $parts = $this->getEnabledRules();
        $minLength = $this->getMinLength();
        $transKey = self::UNRESTRICTED;

        if (count($parts) > 0) {
            $transKey = self::BASE . join(self::SEPARATOR, $parts);
        }

        return $this->translator->trans($transKey, ['{{ length }}' => $minLength]);
    }

    /**
     * Return a map of configured rules
     *
     * @return array
     */
    public function getEnabledRules()
    {
        $parts = [];
        foreach (self::$tooltipPartsMap as $configKey => $partKey) {
            $config = $this->configManager->get($configKey);
            if ($config) {
                $parts[] = $partKey;
            }
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
        return (int) $this->configManager->get(PasswordComplexityValidator::CONFIG_MIN_LENGTH);;
    }
}
