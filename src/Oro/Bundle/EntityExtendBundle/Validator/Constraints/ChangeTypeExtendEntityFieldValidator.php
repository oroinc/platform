<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\Validator\Constraint;

/**
 * Validates changing type of field.
 */
class ChangeTypeExtendEntityFieldValidator extends AbstractFieldValidator
{
    const ALIAS = 'oro_entity_extend.validator.change_type_extend_entity_field';

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $this->assertValidatingValue($value);

        /** @var FieldConfigModel $value */
        $fieldName = $value->getFieldName();

        $existingField = $this->validationHelper->getSimilarExistingFieldData(
            $value->getEntity()->getClassName(),
            $fieldName
        );

        if ($existingField && ($fieldName !== $existingField[0] || $value->getType() !== $existingField[1])) {
            $this->addViolation($constraint->message, $fieldName, $existingField[0]);
        }
    }
}
