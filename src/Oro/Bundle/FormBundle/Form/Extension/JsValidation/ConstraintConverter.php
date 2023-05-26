<?php

namespace Oro\Bundle\FormBundle\Form\Extension\JsValidation;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;

/**
 * The base implementation of a service to convert validation constraint to a form suitable for JS validation.
 */
class ConstraintConverter implements ConstraintConverterInterface
{
    /** @var iterable<ConstraintConverterInterface> */
    private iterable $processors;

    public function __construct(iterable $processors)
    {
        $this->processors = $processors;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, ?FormInterface $form = null): bool
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($constraint, $form)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function convertConstraint(Constraint $constraint, ?FormInterface $form = null): ?Constraint
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($constraint, $form)) {
                return $processor->convertConstraint($constraint, $form);
            }
        }

        return null;
    }
}
