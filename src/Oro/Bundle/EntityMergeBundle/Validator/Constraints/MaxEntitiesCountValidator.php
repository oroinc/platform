<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the number of entities is less or equals to the max limit of entities to merge.
 */
class MaxEntitiesCountValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof MaxEntitiesCount) {
            throw new UnexpectedTypeException($constraint, MaxEntitiesCount::class);
        }
        if (!$value instanceof EntityData) {
            throw new UnexpectedTypeException($value, EntityData::class);
        }

        $maxEntitiesCount = $value->getMetadata()->getMaxEntitiesCount();
        if (count($value->getEntities()) > $maxEntitiesCount) {
            $this->context->addViolation($constraint->message, ['{{ limit }}' => $maxEntitiesCount]);
        }
    }
}
