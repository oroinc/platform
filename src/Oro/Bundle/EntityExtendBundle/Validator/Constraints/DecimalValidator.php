<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DecimalValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_numeric($value) || $value >= PHP_INT_MAX || $value <= -PHP_INT_MAX) {
            throw new UnexpectedTypeException($value, 'numeric');
        }

        $intPart      = (int)(floor(abs($value)));
        $fractionPart = abs($value) - $intPart;

        if (($intPart > 0 && strlen((string)$intPart) > ($constraint->precision - $constraint->scale))
            || ($fractionPart > 0 && strlen(substr(strrchr((string)$fractionPart, '.'), 1)) > $constraint->scale)
        ) {
            $this->context->addViolation(
                $constraint->message,
                [
                    '{{ precision }}' => $constraint->precision,
                    '{{ scale }}'     => $constraint->scale
                ]
            );
        }
    }
}
