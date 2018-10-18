<?php

namespace Oro\Bundle\ApiBundle\Form\Extension;

use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Registers all validation related options for all form types
 * and adds the validation listener if the validation is requested.
 * This extension is similar to Symfony's FormTypeValidatorExtension,
 * but it allows to disable the validation after submit of a form.
 * In Data API, the validation of forms is performed by processors.
 * @see \Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension
 * @see \Oro\Bundle\ApiBundle\Form\FormValidationHandler
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\ValidateForm
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\ValidateIncludedForms
 */
class ValidationExtension extends FormTypeValidatorExtension
{
    public const ENABLE_VALIDATION      = 'enable_validation';
    public const ENABLE_FULL_VALIDATION = 'enable_full_validation';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options[self::ENABLE_VALIDATION]) {
            parent::buildForm($builder, $options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefault(self::ENABLE_VALIDATION, true)
            ->setDefault(self::ENABLE_FULL_VALIDATION, false);
    }
}
