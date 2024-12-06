<?php

namespace Oro\Bundle\FormBundle\Form\Extension\JsValidation;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Represents a service to convert a validation constraint to a form suitable for JS validation.
 */
interface ConstraintConverterInterface
{
    /**
     * Checks if the given validation constraint can be converted to a form suitable for JS validation.
     */
    public function supports(Constraint $constraint, ?FormInterface $form = null): bool;

    /**
     * Converts the given validation constraint to a form suitable for JS validation.
     *
     * @param Constraint $constraint
     * @param FormInterface|null $form
     *
     * @return Constraint|null The constraint suitable for JS validation
     *                         or NULL if the given constraint cannot be used in JS validation
     */
    public function convertConstraint(Constraint $constraint, ?FormInterface $form = null): ?Constraint;
}
