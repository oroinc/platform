<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MinEntitiesCountValidator extends ConstraintValidator
{
    /**
     * {inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof EntityData) {
            throw new InvalidArgumentException(
                sprintf(
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, %s given',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        /* @var EntityData $value */
        $entitiesCount    = sizeof($value->getEntities());

        /* @var MinEntitiesCount $constraint */
        if ($entitiesCount < MinEntitiesCount::MIN_ENTITIES_COUNT) {
            $this->context->addViolation(
                $constraint->message,
                ['%min_count%' => MinEntitiesCount::MIN_ENTITIES_COUNT]
            );
        }
    }
}
