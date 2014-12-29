<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\UIBundle\Tools\ArrayUtils;

class UniqueKeysValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var UniqueKeys $constraint */
        $names = ArrayUtils::arrayColumn($value, 'name');
        if ($names && $names != array_unique($names)) {
            $this->context->addViolation(
                $constraint->message
            );

            return;
        }

        $keys = ArrayUtils::arrayColumn($value, 'key');
        if ($keys && $keys != array_unique($keys, SORT_REGULAR)) {
            $this->context->addViolation(
                $constraint->message
            );

            return;
        }
    }
}
