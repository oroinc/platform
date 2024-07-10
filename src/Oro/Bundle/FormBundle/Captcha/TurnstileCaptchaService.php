<?php

namespace Oro\Bundle\FormBundle\Captcha;

use Oro\Bundle\FormBundle\DependencyInjection\Configuration;
use Oro\Bundle\FormBundle\Form\Type\TurnstileCaptchaType;

/**
 * Cloudflare Turnstile CAPTCHA service implementation.
 */
class TurnstileCaptchaService extends AbstractReCaptchaCompatibleService
{
    public function getFormType(): string
    {
        return TurnstileCaptchaType::class;
    }

    protected function getPublicKeyConfigKey(): string
    {
        return Configuration::getConfigKey(Configuration::TURNSTILE_PUBLIC_KEY);
    }

    protected function getPrivateKeyConfigKey(): string
    {
        return Configuration::getConfigKey(Configuration::TURNSTILE_PRIVATE_KEY);
    }

    protected function getSurveyUrl(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    }
}
