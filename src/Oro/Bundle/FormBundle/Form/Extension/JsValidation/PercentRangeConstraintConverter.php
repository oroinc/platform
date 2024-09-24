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
    #[\Override]
    public function supports(Constraint $constraint, ?FormInterface $form = null): bool
    {
        return $constraint instanceof PercentRange;
    }

    /**
     *
     * @param PercentRange $constraint
     */
    #[\Override]
    public function convertConstraint(Constraint $constraint, ?FormInterface $form = null): ?Constraint
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
