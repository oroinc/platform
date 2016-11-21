<?php

namespace Oro\Bundle\NoteBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\NoteBundle\Validator\Constraints\ContextIsNotEmptyConstraint;

class ContextIsNotEmptyValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!count($value->getActivityTargetEntities())) {
            $this->addViolation($this->context, $constraint);
        }
    }

    /**
     * @param ExecutionContextInterface   $context
     * @param ContextIsNotEmptyConstraint $constraint
     */
    protected function addViolation(ExecutionContextInterface $context, ContextIsNotEmptyConstraint $constraint)
    {
        $context->buildViolation($constraint->message)
            /** @see \Oro\Bundle\ActivityBundle\Form\Extension\ContextsExtension::buildForm */
            ->atPath('contexts')
            ->addViolation();
    }
}
