<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates field name for uniqueness. When generating setter and getter methods, characters `_` and `-` are removed
 * and as result e.g for names `id` and `i_d` methods names are identical.
 */
class UniqueExtendEntityFieldValidator extends AbstractFieldValidator
{
    const ALIAS = 'oro_entity_extend.validator.unique_extend_entity_field';

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $this->assertValidatingValue($value);

        $className = $value->getEntity()->getClassName();
        $fieldName = $value->getFieldName();

        // A special case for `id` field
        if ($this->validationHelper->normalizeFieldName($fieldName) === 'id') {
            $this->addDuplicateFieldViolation($fieldName, 'id', $constraint);
        } else {
            // other fields
            $existingFieldName = $this->validationHelper->getSimilarExistingFieldData($className, $fieldName);
            if ($existingFieldName) {
                $this->addDuplicateFieldViolation($fieldName, $existingFieldName[0], $constraint);
            }
        }
    }

    /**
     * @param string                  $newFieldName
     * @param string                  $existingFieldName
     * @param UniqueExtendEntityField $constraint
     */
    protected function addDuplicateFieldViolation(
        $newFieldName,
        $existingFieldName,
        UniqueExtendEntityField $constraint
    ) {
        if ($newFieldName === $existingFieldName) {
            $this->addViolation($constraint->sameFieldMessage, $newFieldName, $existingFieldName);
        } else {
            $this->addViolation($constraint->similarFieldMessage, $newFieldName, $existingFieldName);
        }
    }
}
