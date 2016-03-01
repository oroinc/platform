<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class MultiEnumSnapshotFieldValidator extends AbstractFieldValidator
{
    const ALIAS = 'oro_entity_extend.validator.multi_enum_snapshot';

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var FieldConfigModel $value */

        $this->assertValidatingValue($value);

        $className = $value->getEntity()->getClassName();
        $fieldName = $value->getFieldName();

        $snapshotSuffixOffset = -strlen(ExtendHelper::ENUM_SNAPSHOT_SUFFIX);
        if (strtolower(substr($fieldName, $snapshotSuffixOffset)) === strtolower(ExtendHelper::ENUM_SNAPSHOT_SUFFIX)) {
            $guessedName = substr($fieldName, 0, $snapshotSuffixOffset);
            if (!empty($guessedName)) {
                $fieldConfig = $this->validationHelper->findExtendFieldConfig($className, $guessedName);
                if ($fieldConfig && $fieldConfig->getId()->getFieldType() === 'multiEnum') {
                    $this->addViolation(
                        $constraint->duplicateSnapshotMessage,
                        $fieldName,
                        $fieldConfig->getId()->getFieldName()
                    );
                }
            }
        } elseif ($value->getType() === 'multiEnum') {
            $guessedName = $fieldName . ExtendHelper::ENUM_SNAPSHOT_SUFFIX;
            $fieldConfig = $this->validationHelper->findExtendFieldConfig($className, $guessedName);
            if ($fieldConfig && $this->validationHelper->hasFieldNameConflict($guessedName, $fieldConfig)) {
                $this->addViolation(
                    $constraint->duplicateFieldMessage,
                    $fieldName,
                    $fieldConfig->getId()->getFieldName()
                );
            }
        }
    }
}
