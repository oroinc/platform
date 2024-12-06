<?php

namespace Oro\Bundle\FormBundle\Form\Extension\JsValidation;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Converts Range constraint for JS validation.
 */
class RangeConstraintConverter implements ConstraintConverterInterface
{
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor
    ) {
    }

    #[\Override]
    public function supports(Constraint $constraint, ?FormInterface $form = null): bool
    {
        return $constraint instanceof Range && !isset($constraint->payload['jsValidation']);
    }

    #[\Override]
    public function convertConstraint(Constraint $constraint, ?FormInterface $form = null): ?Constraint
    {
        /** @var Range $constraint */

        $convertedConstraint = clone $constraint;
        if (null !== $convertedConstraint->maxPropertyPath || null !== $convertedConstraint->minPropertyPath) {
            // Get the parent, because the current data is the numeric value
            $formData = $form?->getParent()?->getData();
            if (\is_object($formData)) {
                $this->setMaxValue($convertedConstraint, $formData);
                $this->setMinValue($convertedConstraint, $formData);
            }
        }

        return $convertedConstraint;
    }

    private function setMaxValue(Range $constraint, object $formData): void
    {
        if (null !== $constraint->maxPropertyPath
            && null === $constraint->max
            && $this->propertyAccessor->isReadable($formData, $constraint->maxPropertyPath)
        ) {
            $constraint->max = $this->propertyAccessor->getValue($formData, $constraint->maxPropertyPath);
            $constraint->maxPropertyPath = null;
        }
    }

    private function setMinValue(Range $constraint, object $formData): void
    {
        if (null !== $constraint->minPropertyPath
            && null === $constraint->min
            && $this->propertyAccessor->isReadable($formData, $constraint->minPropertyPath)
        ) {
            $constraint->min = $this->propertyAccessor->getValue($formData, $constraint->minPropertyPath);
            $constraint->minPropertyPath = null;
        }
    }
}
