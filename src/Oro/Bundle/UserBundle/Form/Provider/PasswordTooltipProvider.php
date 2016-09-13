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
    const TOOLTIP_PREFIX = 'oro.user.password_complexity.tooltip.prefix';
    const TOOLTIP_SEPARATOR = ', ';
    const TOOLTIP_MIN_LENGTH = 'oro.user.password_complexity.tooltip.min_length';
    const TOOLTIP_UPPER_CASE = 'oro.user.password_complexity.tooltip.upper_case';
    const TOOLTIP_NUMBERS = 'oro.user.password_complexity.tooltip.numbers';
    const TOOLTIP_SPECIAL_CHARS = 'oro.user.password_complexity.tooltip.special_chars';

    /**
     * @var array Map of the config keys and their corresponding tooltip translation keys
     */
    public static $tooltipPartsMap = [
        PasswordComplexityValidator::CONFIG_MIN_LENGTH => self::TOOLTIP_MIN_LENGTH,
        PasswordComplexityValidator::CONFIG_UPPER_CASE => self::TOOLTIP_UPPER_CASE,
        PasswordComplexityValidator::CONFIG_NUMBERS => self::TOOLTIP_NUMBERS,
        PasswordComplexityValidator::CONFIG_SPECIAL_CHARS => self::TOOLTIP_SPECIAL_CHARS,
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
        $tooltips = [];
        foreach (self::$tooltipPartsMap as $configKey => $transKey) {
            $config = $this->configManager->get($configKey);
            if ($config) {
                $tooltips[] = $this->translator->trans($transKey, ['{{ value }}' => $config]);
            }
        }

        return $this->translator->trans(self::TOOLTIP_PREFIX) . join(self::TOOLTIP_SEPARATOR, $tooltips);
    }
}
