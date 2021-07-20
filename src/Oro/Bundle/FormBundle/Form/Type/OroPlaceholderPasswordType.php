<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This form type supports placeholder for entered password.
 * Placeholder("*") will be used to indicate that password has been entered
 */
class OroPlaceholderPasswordType extends AbstractType
{
    private const PLACEHOLDER_OPTIONS = 'oro_placeholder_options';
    private const PLACEHOLDER_OPTIONS_ORIGINAL_DATA = 'original_data';

    private const DEFAULT_PLACEHOLDER = '*';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute(self::PLACEHOLDER_OPTIONS, new \ArrayObject());

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                if ($event->getData()) {
                    /** @var \ArrayObject $oroOptions */
                    $oroOptions = $event->getForm()->getConfig()->getAttribute(self::PLACEHOLDER_OPTIONS);

                    // Modify options in \ArrayObject to set default data
                    $oroOptions[self::PLACEHOLDER_OPTIONS_ORIGINAL_DATA] = $event->getData();
                }
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $password = $event->getData();
                $oldPassword = $form->getData();

                if ($password === $this->getPlaceholder((string)$oldPassword)) {
                    $actualPassword = $oldPassword;
                } else {
                    $actualPassword = $password;
                }

                $event->setData($actualPassword);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $oroOptions = $form->getConfig()->getAttribute(self::PLACEHOLDER_OPTIONS);
        $originalFormData = $oroOptions[self::PLACEHOLDER_OPTIONS_ORIGINAL_DATA] ?? false;

        $password = $form->getData();

        // data was not changed during submit
        $dataNotChanged = $originalFormData === $password;

        // We can show placeholder when form not submitted (view page), submitted without errors
        // When the form is submitted with errors we have to show empty field
        $canShowPlaceholder = $dataNotChanged || !$form->isSubmitted() || $form->getRoot()->isValid();

        if ($password && $canShowPlaceholder) {
            $view->vars['value'] = $this->getPlaceholder((string)$password);
        }

        if (false === $options['browser_autocomplete']) {
            // Use soft merge ("+" operator) to preserve 'autocomplete' value if it was already specified.
            $view->vars['attr'] += ['autocomplete' => 'off'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'browser_autocomplete' => false,
        ]);

        $resolver->setAllowedTypes('browser_autocomplete', 'bool');

        // always_empty=true is required for this form type
        $resolver->setAllowedValues('always_empty', true);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return PasswordType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_placeholder_password';
    }

    private function getPlaceholder(string $value): string
    {
        return str_repeat(self::DEFAULT_PLACEHOLDER, mb_strlen($value));
    }
}
