<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Captcha\CaptchaSettingsProviderInterface;
use Oro\Bundle\FormBundle\Validator\Constraints\IsCaptchaVerified;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * CAPTCHA form type facade.
 */
class CaptchaType extends AbstractType
{
    public const NAME = 'oro_captcha';

    public function __construct(
        private CaptchaSettingsProviderInterface $captchaSettingsProvider
    ) {
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('constraints', [new IsCaptchaVerified()]);
    }

    public function getParent()
    {
        return $this->captchaSettingsProvider->getFormType();
    }
}
