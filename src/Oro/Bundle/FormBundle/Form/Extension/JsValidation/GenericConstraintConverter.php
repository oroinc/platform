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
    private ConstraintFactory $constraintFactory;

    private ConstraintConverterInterface $constraintConverter;

    public function __construct(
        ConstraintFactory $constraintFactory,
        ConstraintConverterInterface $constraintConverter,
    ) {
        $this->constraintFactory = $constraintFactory;
        $this->constraintConverter = $constraintConverter;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(Constraint $constraint, ?FormInterface $form = null): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
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
