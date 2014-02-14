<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class UniqueEntityValidator extends DoctrineHelperValidator
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
        $entities       = $value->getEntities();
        $uniqueEntities = [];
        foreach ($entities as $entity) {
            $key = $this->doctrineHelper->getEntityIdentifierValue($entity);

            $uniqueEntities[$key] = $entity;
        }


        if (count($entities) != count($uniqueEntities)) {
            $this->context->addViolation(
            /* @var UniqueEntity $constraint */
                $constraint->message
            );
        }
    }
}
