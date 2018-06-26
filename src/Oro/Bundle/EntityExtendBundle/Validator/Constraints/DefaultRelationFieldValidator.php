<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Symfony\Component\Validator\Constraint;

class DefaultRelationFieldValidator extends AbstractFieldValidator
{
    const ALIAS = 'oro_entity_extend.validator.default_relation_field';

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var FieldConfigModel $value */

        $this->assertValidatingValue($value);

        $className = $value->getEntity()->getClassName();
        $fieldName = $value->getFieldName();

        if (strpos(strtolower($fieldName), trim(ExtendConfigDumper::DEFAULT_PREFIX, '_')) === 0) {
            $guessedName = substr($fieldName, strlen(trim(ExtendConfigDumper::DEFAULT_PREFIX, '_')));
            if (!empty($guessedName)) {
                // note, that we cant create ONE_TO_MANY & MANY_TO_MANY fields via CSV import, so next search is enough
                $fieldConfig = $this->validationHelper->findExtendFieldConfig($className, $guessedName);
                if ($fieldConfig
                    && in_array(
                        $fieldConfig->getId()->getFieldType(),
                        [RelationType::ONE_TO_MANY, RelationType::MANY_TO_MANY],
                        true
                    )
                    && !$fieldConfig->is('without_default')
                ) {
                    $this->addViolation(
                        $constraint->duplicateRelationMessage,
                        $fieldName,
                        $fieldConfig->getId()->getFieldName()
                    );
                }
            }
        } elseif (in_array($value->getType(), [RelationType::ONE_TO_MANY, RelationType::MANY_TO_MANY], true)) {
            $guessedName = ExtendConfigDumper::DEFAULT_PREFIX . $fieldName;
            $existingFieldName = $this->validationHelper->getSimilarExistingFieldData($className, $guessedName);
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
