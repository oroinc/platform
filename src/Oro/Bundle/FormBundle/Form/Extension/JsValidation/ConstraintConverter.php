<?php

namespace Oro\Bundle\FormBundle\Form\Extension\JsValidation;

use Symfony\Component\Validator\Constraint;

/**
 * The base implementation of a service to convert validation constraint to a form suitable for JS validation.
 */
class ConstraintConverter implements ConstraintConverterInterface
{
    /**
     * {@inheritDoc}
     */
    public function convertConstraint(Constraint $constraint): ?Constraint
    {
        return $constraint;
    }
}
