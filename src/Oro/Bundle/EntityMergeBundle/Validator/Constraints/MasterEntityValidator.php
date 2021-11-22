<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the list of entities contains a master entity.
 */
class MasterEntityValidator extends ConstraintValidator
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof MasterEntity) {
            throw new UnexpectedTypeException($constraint, MasterEntity::class);
        }
        if (!$value instanceof EntityData) {
            throw new UnexpectedTypeException($value, EntityData::class);
        }

        if (!$this->containsEntity($value->getEntities(), $value->getMasterEntity())) {
            $this->context->addViolation($constraint->message);
        }
    }

    private function containsEntity(array $entities, object $entityToCheck): bool
    {
        foreach ($entities as $entity) {
            if ($this->doctrineHelper->isEntityEqual($entity, $entityToCheck)) {
                return true;
            }
        }

        return false;
    }
}
