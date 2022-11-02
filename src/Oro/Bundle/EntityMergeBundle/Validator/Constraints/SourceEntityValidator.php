<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the list of entities contains source entities for all fields.
 */
class SourceEntityValidator extends ConstraintValidator
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
        if (!$constraint instanceof SourceEntity) {
            throw new UnexpectedTypeException($constraint, SourceEntity::class);
        }
        if (!$value instanceof EntityData) {
            throw new UnexpectedTypeException($value, EntityData::class);
        }

        $entities = $value->getEntities();
        $fields = $value->getFields();
        foreach ($fields as $field) {
            $sourceEntity = $field->getSourceEntity();
            if (is_object($sourceEntity) && !$this->containsEntity($entities, $sourceEntity)) {
                $this->context->addViolation(
                    $constraint->message,
                    ['{{ limit }}' => $field->getFieldName()]
                );
            }
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
