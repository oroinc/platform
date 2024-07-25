<?php

namespace Oro\Bundle\FormBundle\Captcha;

use Oro\Bundle\FormBundle\DependencyInjection\Configuration;
use Oro\Bundle\FormBundle\Form\Type\HCaptchaType;

/**
 * HCaptcha CAPTCHA service implementation.
 */
class HCaptchaService extends AbstractReCaptchaCompatibleService
{
    public function getFormType(): string
    {
        return HCaptchaType::class;
    }

    protected function getPublicKeyConfigKey(): string
    {
        return Configuration::getConfigKey(Configuration::HCAPTCHA_PUBLIC_KEY);
    }

    protected function getPrivateKeyConfigKey(): string
    {
        return Configuration::getConfigKey(Configuration::HCAPTCHA_PRIVATE_KEY);
    }

    protected function getSurveyUrl(): string
    {
        return 'https://hcaptcha.com/siteverify';
    }
}
