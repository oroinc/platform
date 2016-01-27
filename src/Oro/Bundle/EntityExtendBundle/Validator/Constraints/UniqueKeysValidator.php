<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Component\PhpUtils\ArrayUtil;

class UniqueKeysValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var UniqueKeys $constraint */
        $names = ArrayUtil::arrayColumn($value, 'name');
        if ($names && $names != array_unique($names)) {
            $this->context->addViolation(
                $constraint->message
            );

            return;
        }

        $keys = ArrayUtil::arrayColumn($value, 'key');
        if ($keys && $keys != array_unique($keys, SORT_REGULAR)) {
            $this->context->addViolation(
                $constraint->message
            );

            return;
        }
    }
}
