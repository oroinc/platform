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
    public function convertConstraint(Constraint $constraint/*, ?FormInterface $form = null*/): ?Constraint
    {
        // BC fallback.
        $form = func_get_args()[1] ?? null;
        if ($constraint->maxPropertyPath || $constraint->minPropertyPath) {
            // Get the parent, because the current data is the numeric value
            $formData = $form?->getParent()?->getData();
            if (is_object($formData)) {
                if ($constraint->maxPropertyPath && !$constraint->max) {
                    $constraint->max = $this->getPropertyAccessor()->getValue(
                        $formData,
                        $constraint->maxPropertyPath
                    );
                    $constraint->maxPropertyPath = null;
                }
                if ($constraint->minPropertyPath && !$constraint->min) {
                    $constraint->min = $this->getPropertyAccessor()->getValue(
                        $formData,
                        $constraint->minPropertyPath
                    );
                    $constraint->minPropertyPath = null;
                }
            }
        }

        return $constraint;
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
