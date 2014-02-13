<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class MaxEntitiesCountValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
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
        $maxEntitiesCount = $value->getMetadata()->getMaxEntitiesCount();
        $entitiesCount    = count($value->getEntities());

        if ($entitiesCount > $maxEntitiesCount) {
            $this->context->addViolation(
                /* @var MaxEntitiesCount $constraint */
                $constraint->message,
                ['{{ limit }}' => $maxEntitiesCount]
            );
        }
    }
}
