<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Captcha\CaptchaServiceInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Form type that represents HCaptcha field.
 */
class HCaptchaType extends AbstractType
{
    public const string NAME = 'oro_hcaptcha_token';

    public function __construct(
        private CaptchaServiceInterface $hCaptchaService
    ) {
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace_recursive($view->vars, [
            'attr' => [
                'data-page-component-module' => 'oroform/js/app/components/captcha-hcaptcha-component',
                'data-page-component-options' => json_encode([
                    'site_key' => $this->hCaptchaService->getPublicKey()
                ])
            ]
        ]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return HiddenType::class;
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
