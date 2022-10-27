<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the list of entities does not contain duplicates.
 */
class UniqueEntityValidator extends ConstraintValidator
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
        if (!$constraint instanceof UniqueEntity) {
            throw new UnexpectedTypeException($constraint, UniqueEntity::class);
        }
        if (!$value instanceof EntityData) {
            throw new UnexpectedTypeException($value, EntityData::class);
        }

        $entities = $value->getEntities();
        $uniqueEntities = [];
        foreach ($entities as $entity) {
            $uniqueEntities[$this->doctrineHelper->getEntityIdentifierValue($entity)] = $entity;
        }

        if (count($entities) !== count($uniqueEntities)) {
            $this->context->addViolation($constraint->message);
        }
    }
}
