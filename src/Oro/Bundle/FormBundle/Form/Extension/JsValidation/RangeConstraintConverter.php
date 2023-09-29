<?php

namespace Oro\Bundle\FormBundle\Form\Extension\JsValidation;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Converts Range constraint for JS validation.
 */
class RangeConstraintConverter implements ConstraintConverterInterface
{
    private ?PropertyAccessorInterface $propertyAccessor = null;

    public function __construct(
        ?PropertyAccessorInterface $propertyAccessor = null
    ) {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(Constraint $constraint, ?FormInterface $form = null): bool
    {
        return $constraint instanceof Range && !isset($constraint->payload['jsValidation']);
    }

    /**
     * {@inheritDoc}
     *
     * @param Range $constraint
     */
    public function convertConstraint(Constraint $constraint, ?FormInterface $form = null): ?Constraint
    {
        $convertedConstraint = clone $constraint;
        if ($convertedConstraint->maxPropertyPath !== null || $convertedConstraint->minPropertyPath !== null) {
            // Get the parent, because the current data is the numeric value
            $formData = $form?->getParent()?->getData();
            if (is_object($formData)) {
                if ($convertedConstraint->maxPropertyPath !== null && $convertedConstraint->max === null) {
                    $convertedConstraint->max = $this->getPropertyAccessor()->getValue(
                        $formData,
                        $convertedConstraint->maxPropertyPath
                    );
                    $convertedConstraint->maxPropertyPath = null;
                }
                if ($convertedConstraint->minPropertyPath !== null && $convertedConstraint->min === null) {
                    $convertedConstraint->min = $this->getPropertyAccessor()->getValue(
                        $formData,
                        $convertedConstraint->minPropertyPath
                    );
                    $convertedConstraint->minPropertyPath = null;
                }
            }
        }

        return $convertedConstraint;
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
