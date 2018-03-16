<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ClassMethodNameChecker;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Symfony\Component\Validator\Constraint;

/**
 * Validates method name for uniqueness for field name.
 */
class UniqueExtendEntityMethodNameValidator extends AbstractFieldValidator
{
    const ALIAS = 'oro_entity_extend.validator.unique_extend_entity_method_name';

    /** @var ClassMethodNameChecker */
    protected $methodNameChecker;

    /**
     * @param FieldNameValidationHelper $validationHelper
     * @param ClassMethodNameChecker    $methodNameChecker
     *
     */
    public function __construct(FieldNameValidationHelper $validationHelper, ClassMethodNameChecker $methodNameChecker)
    {
        parent::__construct($validationHelper);

        $this->methodNameChecker = $methodNameChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $this->assertValidatingValue($value);

        $className = $value->getEntity()->getClassName();
        if (!class_exists($className)) {
            return;
        }

        $fieldName = $value->getFieldName();
        $relationType = $this->getRelationType($value->getType());
        if ($relationType) {
            $expectedFieldName = $this->getExpectedRelationFieldName($relationType, $value);
            if ($expectedFieldName && $fieldName !== $expectedFieldName) {
                $this->addViolation($constraint->unexpectedNameMessage, $fieldName, $expectedFieldName);
            }

            return;
        }

        if ($this->hasAtLeastOneMethod($className, $fieldName, ClassMethodNameChecker::$getters)
            || $this->hasAtLeastOneMethod($className, $fieldName, ClassMethodNameChecker::$setters)
            || (
                in_array($value->getType(), RelationType::$anyToAnyRelations, true)
                && $this->hasAtLeastOneMethod($className, $fieldName, ClassMethodNameChecker::$relationMethods)
            )
        ) {
            $this->addViolation($constraint->message, $fieldName, '');
        }
    }

    /**
     * @param string $fieldType
     *
     * @return string|null
     */
    protected function getRelationType($fieldType)
    {
        $typeParts = explode('||', $fieldType);

        return count($typeParts) === 2
            ? ExtendHelper::getRelationType($typeParts[0])
            : null;
    }

    /**
     * @param string           $relationType
     * @param FieldConfigModel $value
     *
     * @return string|null
     */
    protected function getExpectedRelationFieldName($relationType, FieldConfigModel $value)
    {
        if (RelationType::MANY_TO_MANY === $relationType) {
            $typeParts = explode('||', $value->getType());
            $relationConfig = $this->getRelationConfig($value->getEntity(), $typeParts[0]);
            if (!$this->isOwningSideOfRelation($relationConfig)) {
                $fieldId = $this->getRelationFieldId($relationConfig);
                if (null !== $fieldId) {
                    return $fieldId->getFieldName();
                }
            }
        } elseif (RelationType::ONE_TO_MANY === $relationType) {
            $typeParts = explode('||', $value->getType());
            $relationConfig = $this->getRelationConfig($value->getEntity(), $typeParts[0]);
            if ($this->isOwningSideOfRelation($relationConfig)) {
                $fieldId = $this->getRelationFieldId($relationConfig);
                if (null !== $fieldId) {
                    return $fieldId->getFieldName();
                }
            }
        }

        return null;
    }

    /**
     * @param EntityConfigModel $entityConfigModel
     * @param string            $relationKey
     *
     * @return array
     */
    protected function getRelationConfig(EntityConfigModel $entityConfigModel, $relationKey)
    {
        $entityOptions = $entityConfigModel->toArray('extend');

        return !empty($entityOptions['relation'][$relationKey])
            ? $entityOptions['relation'][$relationKey]
            : [];
    }

    /**
     * @param array $relationConfig
     *
     * @return bool
     */
    protected function isOwningSideOfRelation(array $relationConfig)
    {
        return array_key_exists('owner', $relationConfig) && $relationConfig['owner'];
    }

    /**
     * @param array $relationConfig
     *
     * @return FieldConfigId|null
     */
    protected function getRelationFieldId(array $relationConfig)
    {
        $optionName = 'field_id';
        if (!array_key_exists($optionName, $relationConfig)) {
            return null;
        }
        $field = $relationConfig[$optionName];
        if (!$field instanceof FieldConfigId) {
            return null;
        }

        return $field;
    }

    /**
     * @param string   $className
     * @param string   $fieldName
     * @param string[] $methods
     *
     * @return bool
     */
    protected function hasAtLeastOneMethod($className, $fieldName, array $methods)
    {
        $foundMethods = $this->methodNameChecker->getMethods($fieldName, $className, $methods);

        return !empty($foundMethods);
    }
}
