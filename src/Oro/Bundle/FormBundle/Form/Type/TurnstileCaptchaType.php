<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Captcha\CaptchaServiceInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Form type that represents Cloudflare Turnstile field.
 */
class TurnstileCaptchaType extends AbstractType
{
    public const NAME = 'oro_turnstile_token';

    public function __construct(
        private CaptchaServiceInterface $captchaService
    ) {
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace_recursive($view->vars, [
            'attr' => [
                'data-page-component-module' => 'oroform/js/app/components/captcha-turnstile-component',
                'data-page-component-options' => json_encode([
                    'site_key' => $this->captchaService->getPublicKey()
                ])
            ]
        ]);
    }

    public function getParent(): ?string
    {
        return HiddenType::class;
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
