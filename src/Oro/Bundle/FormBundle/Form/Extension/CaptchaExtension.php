<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\FormBundle\Captcha\CaptchaSettingsProviderInterface;
use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Oro\Bundle\FormBundle\Form\Type\CaptchaType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds CAPTCHA field to form.
 * Defines captcha_protection_enabled option for all forms.
 */
class CaptchaExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    private const FIELD_NAME = 'captcha';

    public function __construct(
        private CaptchaSettingsProviderInterface $captchaSettingsProvider
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$this->canAddCaptcha($builder, $options)) {
            return;
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                $form->add(
                    self::FIELD_NAME,
                    CaptchaType::class,
                    [
                        'label' => null,
                        'required' => false,
                        'mapped' => false,
                        'data' => false
                    ]
                );
            }
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('captcha_protection_enabled', false);
        $resolver->setAllowedTypes('captcha_protection_enabled', ['bool']);
    }

    private function canAddCaptcha(FormBuilderInterface $builder, array $options): bool
    {
        if ($builder->has(self::FIELD_NAME)) {
            return false;
        }

        if (!$this->captchaSettingsProvider->isProtectionAvailable()) {
            return false;
        }

        if (!empty($options['captcha_protection_enabled'])) {
            return true;
        }

        return $this->captchaSettingsProvider->isFormProtected($builder->getName());
    }
}
