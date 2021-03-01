<?php

namespace Oro\Bundle\FormBundle\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Validator\Constraints\PercentRange;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Converts PercentRange constraint with Range constraint for JS validation.
 */
class PercentRangeConstraintConverter implements ConstraintConverterInterface
{
    /** @var ConstraintConverterInterface */
    private $innerConverter;

    public function __construct(ConstraintConverterInterface $innerConverter)
    {
        $this->innerConverter = $innerConverter;
    }

    /**
     * {@inheritDoc}
     */
    public function convertConstraint(Constraint $constraint): ?Constraint
    {
        return $constraint instanceof PercentRange
            ? $this->convertPercentRangeToRange($constraint)
            : $this->innerConverter->convertConstraint($constraint);
    }

    private function convertPercentRangeToRange(PercentRange $constraint): Range
    {
        $options = [
            'invalidMessage' => $constraint->invalidMessage
        ];
        if (null !== $constraint->min && null !== $constraint->max) {
            $options['min'] = $constraint->min;
            $options['max'] = $constraint->max;
            $options['notInRangeMessage'] = $constraint->notInRangeMessage;
        } elseif (null !== $constraint->min) {
            $options['min'] = $constraint->min;
            $options['minMessage'] = $constraint->minMessage;
        } elseif (null !== $constraint->max) {
            $options['max'] = $constraint->max;
            $options['maxMessage'] = $constraint->maxMessage;
        }

        return new Range($options);
    }
}
