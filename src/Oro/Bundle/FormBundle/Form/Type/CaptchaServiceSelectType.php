<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Captcha\CaptchaServiceRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type to display a list of available CAPTCHA services.
 */
class CaptchaServiceSelectType extends AbstractType
{
    private const string TRANSLATION_PREFIX = 'oro_form.captcha.service_name.';

    public function __construct(
        private CaptchaServiceRegistry $serviceRegistry
    ) {
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('choice_loader', new CallbackChoiceLoader(function () {
            $choices = [];
            foreach ($this->serviceRegistry->getCaptchaServiceAliases() as $serviceName) {
                $choices[self::TRANSLATION_PREFIX . $serviceName] = $serviceName;
            }

            return $choices;
        }));
    }

    public function getBlockPrefix(): string
    {
        return 'oro_captcha_service_select';
    }
}
