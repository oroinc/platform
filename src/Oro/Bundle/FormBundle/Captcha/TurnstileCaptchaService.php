<?php

namespace Oro\Bundle\FormBundle\Captcha;

use Oro\Bundle\FormBundle\DependencyInjection\Configuration;
use Oro\Bundle\FormBundle\Form\Type\TurnstileCaptchaType;

/**
 * Cloudflare Turnstile CAPTCHA service implementation.
 */
class TurnstileCaptchaService extends AbstractReCaptchaCompatibleService
{
    #[\Override]
    public function getFormType(): string
    {
        return TurnstileCaptchaType::class;
    }

    #[\Override]
    protected function getPublicKeyConfigKey(): string
    {
        return Configuration::getConfigKey(Configuration::TURNSTILE_PUBLIC_KEY);
    }

    #[\Override]
    protected function getPrivateKeyConfigKey(): string
    {
        return Configuration::getConfigKey(Configuration::TURNSTILE_PRIVATE_KEY);
    }

    #[\Override]
    protected function getSurveyUrl(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    }
}
