<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Mock\Captcha;

use Oro\Bundle\FormBundle\Captcha\CaptchaServiceInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Dummy CAPTCHA service implementation for behat tests.
 */
class DummyCaptchaService implements CaptchaServiceInterface
{
    #[\Override]
    public function isConfigured(): bool
    {
        return true;
    }

    #[\Override]
    public function isVerified($value): bool
    {
        return $value === 'valid';
    }

    #[\Override]
    public function getFormType(): string
    {
        return TextType::class;
    }

    #[\Override]
    public function getPublicKey(): ?string
    {
        return 'public_key';
    }
}
