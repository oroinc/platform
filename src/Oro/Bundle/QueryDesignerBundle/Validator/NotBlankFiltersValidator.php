<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate that instance of AbstractQueryDesigner has non empty filters
 */
class NotBlankFiltersValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof AbstractQueryDesigner) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    AbstractQueryDesigner::class,
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }
        $definition = json_decode($value->getDefinition(), true);
        if (empty($definition['filters'])) {
            $this->context->addViolation($constraint->message);
        }
    }
}
