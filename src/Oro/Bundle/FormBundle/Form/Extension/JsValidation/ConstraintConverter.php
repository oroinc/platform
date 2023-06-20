<?php

namespace Oro\Bundle\FormBundle\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;

/**
 * The base implementation of a service to convert validation constraint to a form suitable for JS validation.
 */
class ConstraintConverter implements ConstraintConverterInterface
{
    /** @var iterable<ConstraintConverterInterface> */
    private iterable $processors = [];

    private ConstraintFactory $constraintFactory;

    public function __construct(ConstraintFactory $constraintFactory)
    {
        $this->constraintFactory = $constraintFactory;
    }

    public function setProcessors(iterable $processors): void
    {
        $this->processors = $processors;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, ?FormInterface $form = null): bool
    {
        foreach ($this->processors as $processor) {
            if (method_exists($processor, 'supports') && $processor->supports($constraint, $form)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function convertConstraint(Constraint $constraint/*, ?FormInterface $form = null*/): ?Constraint
    {
        if (!$this->processors) {
            // BC fallback.
            if (isset($constraint->payload['jsValidation']['type'])) {
                return $this->constraintFactory->create(
                    $constraint->payload['jsValidation']['type'],
                    $constraint->payload['jsValidation']['options'] ?? []
                );
            }

            return $constraint;
        }

        $form = func_get_args()[1] ?? null;
        foreach ($this->processors as $processor) {
            if (method_exists($processor, 'supports') && $processor->supports($constraint, $form)) {
                return $processor->convertConstraint($constraint, $form);
            }
        }

        return null;
    }
}
