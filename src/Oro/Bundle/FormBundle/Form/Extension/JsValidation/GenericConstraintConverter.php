<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;

/**
 * The default constraint converter, used for all constraints what do not have own converters.
 * Creates a constraint based on jsValidation payload if any, returns as-is otherwise.
 */
class GenericConstraintConverter implements ConstraintConverterInterface
{
    public function __construct(
        private readonly ConstraintFactory $constraintFactory,
        private readonly ConstraintConverterInterface $constraintConverter,
    ) {
    }

    #[\Override]
    public function supports(Constraint $constraint, ?FormInterface $form = null): bool
    {
        return true;
    }

    #[\Override]
    public function convertConstraint(Constraint $constraint, ?FormInterface $form = null): ?Constraint
    {
        if (isset($constraint->payload['jsValidation']['type'])) {
            return $this->constraintConverter->convertConstraint(
                $this->constraintFactory->create(
                    $constraint->payload['jsValidation']['type'],
                    $constraint->payload['jsValidation']['options'] ?? []
                ),
                $form
            );
        }

        return $constraint;
    }
}
