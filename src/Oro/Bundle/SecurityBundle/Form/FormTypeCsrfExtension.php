<?php

namespace Oro\Bundle\SecurityBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Csrf\EventListener\CsrfValidationListener;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Util\ServerParams;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This file is a modified copy of {@see Symfony\Component\Form\Extension\Csrf\Type\FormTypeCsrfExtension}
 * from Symfony 7.2
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */
class FormTypeCsrfExtension extends AbstractTypeExtension
{
    public function __construct(
        private CsrfTokenManagerInterface $defaultTokenManager,
        private bool $defaultEnabled = true,
        private string $defaultFieldName = '_token',
        private ?TranslatorInterface $translator = null,
        private ?string $translationDomain = null,
        private ?ServerParams $serverParams = null,
        private array $fieldAttr = [],
        private ?string $defaultTokenId = null,
    ) {
    }

    /**
     * Adds a CSRF field to the form when the CSRF protection is enabled.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['csrf_protection']) {
            return;
        }

        $builder
            ->addEventSubscriber(new CsrfValidationListener(
                $options['csrf_field_name'],
                $options['csrf_token_manager'],
                $options['csrf_token_id'] ?: ($builder->getName() ?: $builder->getType()->getInnerType()::class),
                $options['csrf_message'],
                $this->translator,
                $this->translationDomain,
                $this->serverParams
            ))
        ;
    }

    /**
     * Adds a CSRF field to the root form view.
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['csrf_protection'] && !$view->parent && $options['compound']) {
            $factory = $form->getConfig()->getFormFactory();
            $tokenId = $options['csrf_token_id'] ?:
                ($form->getName() ?: $form->getConfig()->getType()->getInnerType()::class);
            $data = (string) $options['csrf_token_manager']->getToken($tokenId);

            $csrfForm = $factory->createNamed($options['csrf_field_name'], HiddenType::class, $data, [
                'block_prefix' => 'csrf_token',
                'mapped' => false,
                'attr' => $this->fieldAttr + ['autocomplete' => 'off'],
            ]);

            $view->children[$options['csrf_field_name']] = $csrfForm->createView($view);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        if ($defaultTokenId = $this->defaultTokenId) {
            $defaultTokenManager = $this->defaultTokenManager;

            $defaultTokenId = static fn (Options $options) =>
                $options['csrf_token_manager'] === $defaultTokenManager ? $defaultTokenId : null;
        }

        $resolver->setDefaults([
            'csrf_protection' => $this->defaultEnabled,
            'csrf_field_name' => $this->defaultFieldName,
            'csrf_message' => 'The CSRF token is invalid. Please try to resubmit the form.',
            'csrf_token_manager' => $this->defaultTokenManager,
            'csrf_token_id' => $defaultTokenId,
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
