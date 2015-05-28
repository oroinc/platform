<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DecimalValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     * @param Decimal $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_numeric($value)) {
            throw new UnexpectedTypeException($value, 'numeric');
        }

        $invalid = false;

        $intPart      = intval(floor(abs($value)));
        $fractionPart = abs($value) - $intPart;

        if (($intPart > 0 && strlen((string) $intPart) > ($constraint->precision - $constraint->scale))
            || ($fractionPart > 0 && strlen(substr(strrchr((string) $fractionPart, '.'), 1)) > $constraint->scale)
        ) {
            $invalid = true;
        }

        if ($invalid) {
            $this->context->addViolation($constraint->message, [
                '{{ precision }}' => $constraint->precision,
                '{{ scale }}'     => $constraint->scale,
            ]);
        }
    }
}
