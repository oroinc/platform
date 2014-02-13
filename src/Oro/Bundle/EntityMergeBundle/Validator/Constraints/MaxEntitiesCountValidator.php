<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MaxEntitiesCountValidator extends ConstraintValidator
{
    /**
     * {inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /* @var EntityData $value */
        $maxEntitiesCount = $value->getMetadata()->getMaxEntitiesCount();
        $entitiesCount    = sizeof($value->getEntities());

        if ($entitiesCount > $maxEntitiesCount) {
            $this->context->addViolation(
                /* @var MaxEntitiesCount $constraint */
                $constraint->message,
                ['%max_count%' => $maxEntitiesCount]
            );
        }
    }
}
