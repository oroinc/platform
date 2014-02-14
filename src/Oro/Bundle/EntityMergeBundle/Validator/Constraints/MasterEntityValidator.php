<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class MasterEntityValidator extends DoctrineHelperValidator
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
        $entities = $value->getEntities();
        $masterEntity = $value->getMasterEntity();

        foreach ($entities as $entity) {
            if ($this->doctrineHelper->isEntityEqual($entity, $masterEntity)) {
                return;
            }
        }

        $this->context->addViolation(
        /* @var MasterEntity $constraint */
            $constraint->message
        );
    }
}
