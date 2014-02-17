<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class SourceEntityValidator extends DoctrineHelperValidator
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
        $fields = $value->getFields();

        foreach ($fields as $field) {
            $sourceEntity = $field->getSourceEntity();
            foreach ($entities as $entity) {
                if ($this->doctrineHelper->isEntityEqual($entity, $sourceEntity)) {
                    break 2;
                }
            }

            $this->context->addViolation(
                /* @var SourceEntity $constraint */
                $constraint->message,
                ['{{ limit }}' => $field->getFieldName()]
            );
        }
    }
}
