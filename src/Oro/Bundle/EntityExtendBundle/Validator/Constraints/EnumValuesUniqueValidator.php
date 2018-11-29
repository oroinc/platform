<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that enum options does not have duplicates.
 */
class EnumValuesUniqueValidator extends ConstraintValidator
{
    public const ALIAS = 'oro_entity_extend.validator.unique_enum_values';

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (empty($value)) {
            return;
        }

        $occurrences = [];
        foreach ($value as $option) {
            $label = $option['label'];
            $occurrences[$label] = ($occurrences[$label] ?? 0) + 1;
        }

        $duplicates = [];
        foreach ($occurrences as $label => $count) {
            if ($count > 1) {
                $duplicates[] = $label;
            }
        }

        if (!empty($duplicates)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', "'" . implode("', '", $duplicates) . "'")
                ->setPlural(count($duplicates))
                ->addViolation();
        }
    }
}
