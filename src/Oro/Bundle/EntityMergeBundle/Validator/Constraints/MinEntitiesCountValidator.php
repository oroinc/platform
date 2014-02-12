<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MinEntitiesCountValidator extends ConstraintValidator
{
    /**
     * {inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /* @var EntityData $value */
        $entitiesCount    = sizeof($value->getEntities());

        /* @var MinEntitiesCount $constraint */
        if ($entitiesCount < $constraint::MIN_ENTITIES_COUNT) {
            $this->context->addViolation(
                $constraint->message,
                ['%min_count%' => $constraint::MIN_ENTITIES_COUNT]
            );
        }
    }
}
