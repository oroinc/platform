<?php

namespace Oro\Bundle\UserBundle\Form\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Generates a tooltip text for the system configured password complexity requirements
 */
class PasswordTooltipProvider
{
    const PASSWORD_TOOLTIP_PREFIX = 'oro.user.password_complexity.tooltip.prefix';

    /**
     * @var array Map of tooltip parts and config keys that enable them
     */
    public static $tooltipPartsMap = [
        'oro_user.password_min_length' => 'oro.user.password_complexity.tooltip.min_length',
        'oro_user.password_upper_case' => 'oro.user.password_complexity.tooltip.upper_case',
        'oro_user.password_numbers' => 'oro.user.password_complexity.tooltip.numbers',
        'oro_user.password_special_chars' => 'oro.user.password_complexity.tooltip.special_chars',
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
        foreach (self::$tooltipPartsMap as $configKey => $text) {
            $config = $this->configManager->get($configKey);
            if ((bool) $config) {
                $tooltips[] = $this->translator->trans($text, ['{{ value }}' => $config]);
            }
        }

        return $this->translator->trans(self::PASSWORD_TOOLTIP_PREFIX) . ' ' . join(', ', $tooltips);
    }
}
