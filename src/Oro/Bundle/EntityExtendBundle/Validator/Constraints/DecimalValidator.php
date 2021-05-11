<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Brick\Math\BigDecimal;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

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
            $this->context->addViolation($constraint->messageNotNumeric);
            return;
        }

        $bigDecimalValue = BigDecimal::of($value)->abs();
        $intPart = $bigDecimalValue->getIntegralPart();

        if (($intPart > 0 && \strlen($intPart) > ($constraint->precision - $constraint->scale))
            || ($bigDecimalValue->getScale() > $constraint->scale)
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
