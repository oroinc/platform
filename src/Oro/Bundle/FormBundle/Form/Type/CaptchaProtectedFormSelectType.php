<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\FormBundle\Captcha\CaptchaProtectedFormsRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
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
        $resolver->define('scope')
            ->allowedTypes('string')
            ->default(GlobalScopeManager::SCOPE_NAME);

        $resolver->setDefault('choice_loader', function (Options $options) {
            return new CallbackChoiceLoader(function () use ($options) {
                $choices = [];
                $protectedFormNames = $this->formsRegistry->getProtectedFormsByScope($options['scope']);
                foreach ($protectedFormNames as $protectedFormName) {
                    $choices[self::TRANSLATION_PREFIX . $protectedFormName] = $protectedFormName;
                }

                return $choices;
            });
        });
        $resolver->setDefault('multiple', true);
        $resolver->setDefault('expanded', true);
    }

    public function getBlockPrefix(): string
    {
        return 'oro_captcha_protected_form_select';
    }
}
