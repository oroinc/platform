<?php

namespace Oro\Bundle\FormBundle\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Validator\Constraints\PercentRange;
use Symfony\Component\Form\FormInterface;
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
    public function supports(Constraint $constraint, ?FormInterface $form = null): bool
    {
        return $constraint instanceof PercentRange;
    }

    /**
     * {@inheritDoc}
     *
     * @param PercentRange $constraint
     */
    public function convertConstraint(Constraint $constraint/*, ?FormInterface $form = null*/): ?Constraint
    {
        // BC fallback.
        $form = func_get_args()[1] ?? null;
        return $this->supports($constraint)
            ? $this->convertPercentRangeToRange($constraint)
            : $this->innerConverter->convertConstraint($constraint, $form);
    }

    private function convertPercentRangeToRange(PercentRange $constraint): Range
    {
        $options = [
            'invalidMessage' => $constraint->invalidMessage,
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
