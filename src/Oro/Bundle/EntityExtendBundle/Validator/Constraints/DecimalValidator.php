<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates given value to be correct decimal number with expected precision and scale
 */
class DecimalValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($this->isEmpty($value)) {
            return;
        }

        if (!is_numeric($value) || $value >= PHP_INT_MAX || $value <= -PHP_INT_MAX) {
            throw new UnexpectedTypeException($value, 'numeric');
        }

        $intPart = (int)floor(abs($value));
        $fractionPart = substr(strrchr((string)$value, '.'), 1);

        if (($intPart > 0 && \strlen((string)$intPart) > ($constraint->precision - $constraint->scale))
            || ($fractionPart && \strlen($fractionPart) > $constraint->scale)
        ) {
            $this->context->addViolation(
                $constraint->message,
                [
                    '{{ precision }}' => $constraint->precision,
                    '{{ scale }}' => $constraint->scale
                ]
            );
        }
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function isEmpty($value): bool
    {
        return null === $value || '' === $value;
    }
}
