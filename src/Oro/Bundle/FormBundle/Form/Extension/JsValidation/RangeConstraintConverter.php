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
                $this->setMaxValue($convertedConstraint, $formData);
                $this->setMinValue($convertedConstraint, $formData);
            }
        }

        return $convertedConstraint;
    }

    private function setMaxValue($convertedConstraint, $formData): void
    {
        if ($convertedConstraint->maxPropertyPath !== null && $convertedConstraint->max === null) {
            if ($this->getPropertyAccessor()->isReadable($formData, $convertedConstraint->maxPropertyPath)) {
                $convertedConstraint->max = $this->getPropertyAccessor()->getValue(
                    $formData,
                    $convertedConstraint->maxPropertyPath
                );
                $convertedConstraint->maxPropertyPath = null;
            }
        }
    }

    private function setMinValue($convertedConstraint, $formData): void
    {
        if ($convertedConstraint->minPropertyPath !== null && $convertedConstraint->min === null) {
            if ($this->getPropertyAccessor()->isReadable($formData, $convertedConstraint->minPropertyPath)) {
                $convertedConstraint->min = $this->getPropertyAccessor()->getValue(
                    $formData,
                    $convertedConstraint->minPropertyPath
                );
                $convertedConstraint->minPropertyPath = null;
            }
        }
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
