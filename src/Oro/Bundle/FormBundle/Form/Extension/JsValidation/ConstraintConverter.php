<?php

namespace Oro\Bundle\FormBundle\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use Symfony\Component\Validator\Constraint;

/**
 * The base implementation of a service to convert validation constraint to a form suitable for JS validation.
 * Creates a constraint based on jsValidation payload if any, returns as-is otherwise.
 */
class ConstraintConverter implements ConstraintConverterInterface
{
    private ConstraintFactory $constraintFactory;

    public function __construct(ConstraintFactory $constraintFactory)
    {
        $this->constraintFactory = $constraintFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function convertConstraint(Constraint $constraint): ?Constraint
    {
        if (isset($constraint->payload['jsValidation']['type'])) {
            return $this->constraintFactory->create(
                $constraint->payload['jsValidation']['type'],
                $constraint->payload['jsValidation']['options'] ?? []
            );
        }

        return $constraint;
    }
}
