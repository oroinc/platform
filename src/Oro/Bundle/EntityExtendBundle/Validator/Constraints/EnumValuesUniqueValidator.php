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

        $foundDuplicates = [];
        foreach ($value as $currentKey => $currentArray) {
            $currentLabel = $currentArray['label'];
            foreach ($value as $searchKey => $searchArray) {
                if ($currentKey !== $searchKey && $searchArray['label'] === $currentLabel) {
                    if (!in_array($currentLabel, $foundDuplicates, true)) {
                        $foundDuplicates[] = $currentLabel;
                    }
                }
            }
        }

        if (!empty($foundDuplicates)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', "'" . implode("', '", $foundDuplicates) . "'")
                ->setPlural(count($foundDuplicates))
                ->addViolation();
        }
    }
}
