<?php

namespace Oro\Bundle\FormBundle\Captcha;

/**
 * Provides settings required to use CAPTCHA service.
 */
interface CaptchaSettingsProviderInterface
{
    /**
     * Checks for CAPTCHA protection availability.
     */
    public function isProtectionAvailable(): bool;

    /**
     * Checks if form is eligible for CAPTCHA protection
     */
    public function isFormProtected(string $formName): bool;

    /**
     * Return form type of CAPTCHA service.
     */
    public function getFormType(): string;
}
