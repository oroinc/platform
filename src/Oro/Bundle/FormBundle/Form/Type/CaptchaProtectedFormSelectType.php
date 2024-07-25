<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Captcha\CaptchaProtectedFormsRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A field to display the list of CAPTCHA protected forms that were tagged with oro_form.captcha_protected
 */
class CaptchaProtectedFormSelectType extends AbstractType
{
    private const TRANSLATION_PREFIX = 'oro_form.captcha.protected_form_name.';

    public function __construct(
        private CaptchaProtectedFormsRegistry $formsRegistry
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
            foreach ($this->formsRegistry->getProtectedForms() as $protectedFormName) {
                $choices[self::TRANSLATION_PREFIX . $protectedFormName] = $protectedFormName;
            }

            return $choices;
        }));
        $resolver->setDefault('multiple', true);
        $resolver->setDefault('expanded', true);
    }

    public function getBlockPrefix(): string
    {
        return 'oro_captcha_protected_form_select';
    }
}
