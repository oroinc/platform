<?php

namespace Oro\Bundle\FormBundle\Layout\DataProvider;

use Oro\Bundle\FormBundle\Captcha\CaptchaSettingsProviderInterface;

/**
 * Provides information about the availability of the CAPTCHA for the layout component.
 */
class CaptchaProvider
{
    public function __construct(
        private CaptchaSettingsProviderInterface $captchaSettingsProvider
    ) {
    }

    public function isProtectionAvailable(): bool
    {
        return $this->captchaSettingsProvider->isProtectionAvailable();
    }

    public function isFormProtected(string $formName): bool
    {
        return $this->captchaSettingsProvider->isFormProtected($formName);
    }
}
