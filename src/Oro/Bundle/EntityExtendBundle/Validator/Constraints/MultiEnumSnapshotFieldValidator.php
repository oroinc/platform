<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Validator\Constraint;

/**
 * Validates Duplication of MultiEnum entity field.
 */
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
                $existingFieldName = $this->validationHelper->getSimilarExistingFieldData($className, $guessedName);
                if ($existingFieldName && ExtendHelper::isMultiEnumType($existingFieldName[1])) {
                    $this->addViolation(
                        $constraint->duplicateSnapshotMessage,
                        $fieldName,
                        $existingFieldName[0]
                    );
                }
            }
        } elseif (ExtendHelper::isMultiEnumType($value->getType())) {
            $existingFieldName = $this->validationHelper->getSimilarExistingFieldData(
                $className,
                $fieldName . ExtendHelper::ENUM_SNAPSHOT_SUFFIX
            );

            if ($existingFieldName) {
                $this->addViolation(
                    $constraint->duplicateFieldMessage,
                    $fieldName,
                    $existingFieldName[0]
                );
            }
        }
    }
}
