<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates multidimensional array, or objects collection $value
 * Check if there are no duplicates among collection item['name'] and item['key'] values
 */
class UniqueKeysValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var UniqueKeys $constraint */
        $names = \array_column($value, 'name');
        if ($names && $names != array_unique($names)) {
            $this->context->addViolation(
                $constraint->message
            );

            return;
        }

        $keys = \array_column($value, 'key');
        if ($keys && $keys != array_unique($keys, SORT_REGULAR)) {
            $this->context->addViolation(
                $constraint->message
            );

            return;
        }
    }
}
