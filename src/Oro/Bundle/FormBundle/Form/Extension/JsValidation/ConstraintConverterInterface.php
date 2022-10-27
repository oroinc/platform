<?php

namespace Oro\Bundle\FormBundle\Form\Extension\JsValidation;

use Symfony\Component\Validator\Constraint;

/**
 * Represents a service to convert a validation constraint to a form suitable for JS validation.
 */
interface ConstraintConverterInterface
{
    /**
     * Converts the given validation constraint to a form suitable for JS validation.
     *
     * @param Constraint $constraint
     *
     * @return Constraint|null The constraint suitable for JS validation
     *                         or NULL if the given constraint cannot be used in JS validation
     */
    public function convertConstraint(Constraint $constraint): ?Constraint;
}
