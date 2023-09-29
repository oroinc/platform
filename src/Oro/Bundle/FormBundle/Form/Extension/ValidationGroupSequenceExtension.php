<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GroupSequence;

/**
 * Converts nested arrays of validation groups to {@see GroupSequence}.
 */
class ValidationGroupSequenceExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->addNormalizer(
            'validation_groups',
            function (Options $options, $validationGroups) {
                if (is_array($validationGroups)) {
                    return ValidationGroupUtils::resolveValidationGroups($validationGroups);
                }

                return $validationGroups;
            },
            true
        );
    }
}
