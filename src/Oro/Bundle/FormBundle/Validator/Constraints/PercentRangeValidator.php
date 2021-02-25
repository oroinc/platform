<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a percentage value is in a specific range.
 */
class PercentRangeValidator extends ConstraintValidator
{
    private const COMPARISON_PRECISION = 12;

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof PercentRange) {
            throw new UnexpectedTypeException($constraint, PercentRange::class);
        }

        if (null === $value) {
            return;
        }

        if (is_numeric($value)) {
            $valueToCompare = $this->getValueToCompare((float)$value, $constraint);
            if (PercentRange::INTEGER === $constraint->type && !$this->isInteger($valueToCompare)) {
                $this->context->buildViolation($constraint->notIntegerMessage)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setCode(Range::INVALID_CHARACTERS_ERROR)
                    ->addViolation();
            } elseif ($this->isNotInRange($valueToCompare, $constraint)) {
                $this->context->buildViolation($constraint->notInRangeMessage)
                    ->setParameter('{{ value }}', $this->formatValue($valueToCompare))
                    ->setParameter('{{ min }}', $this->formatPercentValue($constraint->min))
                    ->setParameter('{{ max }}', $this->formatPercentValue($constraint->max))
                    ->setCode(Range::NOT_IN_RANGE_ERROR)
                    ->addViolation();
            } elseif ($this->isTooHigh($valueToCompare, $constraint)) {
                $this->context->buildViolation($constraint->maxMessage)
                    ->setParameter('{{ value }}', $this->formatValue($valueToCompare))
                    ->setParameter('{{ limit }}', $this->formatPercentValue($constraint->max))
                    ->setCode(Range::TOO_HIGH_ERROR)
                    ->addViolation();
            } elseif ($this->isTooLow($valueToCompare, $constraint)) {
                $this->context->buildViolation($constraint->minMessage)
                    ->setParameter('{{ value }}', $this->formatValue($valueToCompare))
                    ->setParameter('{{ limit }}', $this->formatPercentValue($constraint->min))
                    ->setCode(Range::TOO_LOW_ERROR)
                    ->addViolation();
            }
        } else {
            $this->context->buildViolation($constraint->invalidMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Range::INVALID_CHARACTERS_ERROR)
                ->addViolation();
        }
    }

    private function getValueToCompare(float $value, PercentRange $constraint): float
    {
        return PercentRange::FRACTIONAL === $constraint->type
            ? round($value * 100.0, self::COMPARISON_PRECISION)
            : $value;
    }

    private function isNotInRange(float $value, PercentRange $constraint): bool
    {
        return
            null !== $constraint->min
            && null !== $constraint->max
            && ($value < $constraint->min || $value > $constraint->max);
    }

    private function isTooHigh(float $value, PercentRange $constraint): bool
    {
        return null !== $constraint->max && $value > $constraint->max;
    }

    private function isTooLow(float $value, PercentRange $constraint): bool
    {
        return null !== $constraint->min && $value < $constraint->min;
    }

    private function isInteger(float $value): bool
    {
        $val = abs($value);

        return round($val - floor($val), self::COMPARISON_PRECISION) === 0.0;
    }

    private function formatPercentValue(float $value): string
    {
        return $this->formatValue($value) . '%';
    }
}
