<?php

namespace Oro\Component\Testing\Unit\Form\Extension\Stub;

use Symfony\Component\Form\Extension\Validator\Type\BaseValidatorExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormTypeValidatorExtensionStub extends BaseValidatorExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        // Constraint should always be converted to an array
        $constraintsNormalizer = function (Options $options, $constraints) {
            return is_object($constraints) ? [$constraints] : (array)$constraints;
        };

        $resolver->setDefaults(
            [
                'error_mapping' => [],
                'constraints' => [],
                'cascade_validation' => false,
                'invalid_message' => 'This value is not valid.',
                'invalid_message_parameters' => [],
                'allow_extra_fields' => false,
                'extra_fields_message' => 'This form should not contain extra fields.',
            ]
        );

        $resolver->setNormalizer('constraints', $constraintsNormalizer);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
