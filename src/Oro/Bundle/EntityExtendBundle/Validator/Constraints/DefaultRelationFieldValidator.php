<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

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
