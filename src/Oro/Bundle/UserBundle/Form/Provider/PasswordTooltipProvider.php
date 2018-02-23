<?php

namespace Oro\Bundle\UserBundle\Form\Provider;

use Oro\Bundle\UserBundle\Provider\PasswordComplexityConfigProvider;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Generates a tooltip text for the system configured password complexity requirements
 */
class PasswordTooltipProvider
{
    const BASE = 'oro.user.password_complexity.';
    const UNRESTRICTED = self::BASE . 'unrestricted';
    const MIN_LENGTH = 'min_length';
    const LOWER_CASE = 'lower_case';
    const UPPER_CASE = 'upper_case';
    const NUMBERS = 'numbers';
    const SPECIAL_CHARS = 'special_chars';
    const SEPARATOR = '_';

    /**
     * @var array Map of the config keys and their corresponding tooltip translation keys
     */
    public static $tooltipTransKeysMap = [
        PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH => self::MIN_LENGTH,
        PasswordComplexityConfigProvider::CONFIG_LOWER_CASE => self::LOWER_CASE,
        PasswordComplexityConfigProvider::CONFIG_UPPER_CASE => self::UPPER_CASE,
        PasswordComplexityConfigProvider::CONFIG_NUMBERS => self::NUMBERS,
        PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS => self::SPECIAL_CHARS,
    ];

    /** @var PasswordComplexityConfigProvider */
    protected $configProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param PasswordComplexityConfigProvider $configProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(PasswordComplexityConfigProvider $configProvider, TranslatorInterface $translator)
    {
        $this->configProvider = $configProvider;
        $this->translator = $translator;
    }

    /**
     * Build the tooltip text based on system configuration for password complexity
     *
     * @return string
     */
    public function getTooltip()
    {
        $enabledRules = array_filter($this->configProvider->getAllRules());
        $parts = array_intersect_key(self::$tooltipTransKeysMap, $enabledRules);
        $transKey = self::UNRESTRICTED;

        if (count($parts) > 0) {
            // compose a translation key from enabled rules
            $transKey = self::BASE . implode(self::SEPARATOR, $parts);
        }

        return $this->translator->trans($transKey, ['{{ length }}' => $this->configProvider->getMinLength()]);
    }
}
