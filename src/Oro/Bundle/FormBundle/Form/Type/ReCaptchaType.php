<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Captcha\CaptchaServiceInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Form type that represents Google ReCaptcha v3 field.
 */
class ReCaptchaType extends AbstractType
{
    public const string NAME = 'oro_recaptcha_token';

    public function __construct(
        private CaptchaServiceInterface $reCaptchaService
    ) {
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        preg_match('/^([^\[]+)/', $view->vars['full_name'], $matches);
        if ($matches && isset($matches[1])) {
            $action = $matches[1];
        } else {
            $action = $form->getName();
        }

        $view->vars = array_replace_recursive($view->vars, [
            'attr' => [
                'data-page-component-module' => 'oroform/js/app/components/captcha-recaptcha-component',
                'data-page-component-options' => json_encode([
                    'site_key' => $this->reCaptchaService->getPublicKey(),
                    'action' => $action
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
