<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotBlankValidator as BaseNotBlankValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Adds a check for empty collection in additional to default validation rules of NotBlank constraint.
 */
class NotBlankValidator extends BaseNotBlankValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        parent::validate($value, $constraint);

        if ($value instanceof Collection && $value->isEmpty()) {
            if ($this->context instanceof ExecutionContextInterface) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setCode(NotBlank::IS_BLANK_ERROR)
                    ->addViolation();
            } else {
                $this->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setCode(NotBlank::IS_BLANK_ERROR)
                    ->addViolation();
            }
        }
    }
}
