<?php

namespace Oro\Bundle\FormBundle\Captcha;

/**
 * Interface for CAPTCHA services.
 */
interface CaptchaServiceInterface
{
    /**
     * Check if CAPTCHA service is fully configured and is ready to use.
     */
    public function isConfigured(): bool;

    /**
     * Check if CAPTCHA token is verified by the service and user passed all checks.
     */
    public function isVerified($value): bool;

    /**
     * Get CAPTCHA service Public Key, may be named Site Key for some services
     */
    public function getPublicKey(): ?string;

    /**
     * Get FormType that represents CAPTCHA field.
     */
    public function getFormType(): string;
}
