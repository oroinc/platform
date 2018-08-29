<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\EntitySerializer\FieldConfig;
use Symfony\Component\Validator\Constraint;

/**
 * Represents the configuration of an entity field.
 *
 * @method EntityDefinitionConfig|null getTargetEntity()
 * @method EntityDefinitionConfig|null setTargetEntity(EntityDefinitionConfig $targetEntity = null)
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityDefinitionFieldConfig extends FieldConfig implements FieldConfigInterface
{
    /** @var string|null */
    protected $dataType;

    /**
     * {@inheritdoc}
     */
    public function toArray($excludeTargetEntity = false)
    {
        $result = parent::toArray($excludeTargetEntity);
        if (null !== $this->dataType) {
            $result[ConfigUtil::DATA_TYPE] = $this->dataType;
        }
        if (isset($result[ConfigUtil::COLLAPSE]) && false === $result[ConfigUtil::COLLAPSE]) {
            unset($result[ConfigUtil::COLLAPSE]);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return
            null === $this->dataType
            && parent::isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return \array_key_exists($key, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $defaultValue = null)
    {
        if (!\array_key_exists($key, $this->items)) {
            return $defaultValue;
        }

        return $this->items[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        if (null !== $value) {
            $this->items[$key] = $value;
        } else {
            unset($this->items[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        unset($this->items[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return \array_keys($this->items);
    }

    /**
     * Indicates whether the exclusion flag is set explicitly.
     *
     * @return bool
     */
    public function hasExcluded()
    {
        return null !== $this->exclude;
    }

    /**
     * Indicates whether the description attribute exists.
     *
     * @return bool
     */
    public function hasDescription()
    {
        return $this->has(ConfigUtil::DESCRIPTION);
    }

    /**
     * Gets the value of the description attribute.
     *
     * @return string|Label|null
     */
    public function getDescription()
    {
        return $this->get(ConfigUtil::DESCRIPTION);
    }

    /**
     * Sets the value of the description attribute.
     *
     * @param string|Label|null $description
     */
    public function setDescription($description)
    {
        if ($description) {
            $this->items[ConfigUtil::DESCRIPTION] = $description;
        } else {
            unset($this->items[ConfigUtil::DESCRIPTION]);
        }
    }

    /**
     * Indicates whether the data type is set.
     *
     * @return bool
     */
    public function hasDataType()
    {
        return null !== $this->dataType;
    }

    /**
     * Gets expected data type of the filter value.
     *
     * @return string|null
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * Sets expected data type of the filter value.
     *
     * @param string|null $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }

    /**
     * Indicates whether the direction option is set explicitly.
     * If this option is not set, both the request and the response can contain this field.
     *
     * @return bool
     */
    public function hasDirection()
    {
        return $this->has(ConfigUtil::DIRECTION);
    }

    /**
     * Sets a value that indicates whether the field is input-only, output-only or bidirectional.
     *
     * * The "input-only" means that the request data can contain this field,
     *   but the response data cannot.
     * * The "output-only" means that the response data can contain this field,
     *   but the request data cannot.
     * * The "bidirectional" means that both the request data and the response data can contain this field.
     *
     * The "bidirectional" is the default behaviour.
     *
     * @param string|null $direction Can be "input-only", "output-only", "bidirectional"
     *                               or NULL to remove this option and use default behaviour for it
     */
    public function setDirection($direction)
    {
        if ($direction) {
            if (ConfigUtil::DIRECTION_INPUT_ONLY !== $direction
                && ConfigUtil::DIRECTION_OUTPUT_ONLY !== $direction
                && ConfigUtil::DIRECTION_BIDIRECTIONAL !== $direction
            ) {
                throw new \InvalidArgumentException(\sprintf(
                    'The possible values for the direction are "%s", "%s" or "%s".',
                    ConfigUtil::DIRECTION_INPUT_ONLY,
                    ConfigUtil::DIRECTION_OUTPUT_ONLY,
                    ConfigUtil::DIRECTION_BIDIRECTIONAL
                ));
            }
            $this->items[ConfigUtil::DIRECTION] = $direction;
        } else {
            unset($this->items[ConfigUtil::DIRECTION]);
        }
    }

    /**
     * Indicates whether the request data can contain this field.
     *
     * @return bool
     */
    public function isInput()
    {
        if (!\array_key_exists(ConfigUtil::DIRECTION, $this->items)) {
            return true;
        }

        $direction = $this->items[ConfigUtil::DIRECTION];

        return
            ConfigUtil::DIRECTION_INPUT_ONLY === $direction
            || ConfigUtil::DIRECTION_BIDIRECTIONAL === $direction;
    }

    /**
     * Indicates whether the response data can contain this field.
     *
     * @return bool
     */
    public function isOutput()
    {
        if (!\array_key_exists(ConfigUtil::DIRECTION, $this->items)) {
            return true;
        }

        $direction = $this->items[ConfigUtil::DIRECTION];

        return
            ConfigUtil::DIRECTION_OUTPUT_ONLY === $direction
            || ConfigUtil::DIRECTION_BIDIRECTIONAL === $direction;
    }

    /**
     * Indicates whether the field represents a meta information.
     *
     * @return bool
     */
    public function isMetaProperty()
    {
        return $this->get(ConfigUtil::META_PROPERTY, false);
    }

    /**
     * Sets a flag indicates whether the field represents a meta information.
     *
     * @param bool $isMetaProperty
     */
    public function setMetaProperty($isMetaProperty)
    {
        if ($isMetaProperty) {
            $this->items[ConfigUtil::META_PROPERTY] = $isMetaProperty;
        } else {
            unset($this->items[ConfigUtil::META_PROPERTY]);
        }
    }

    /**
     * Gets the name by which the meta property should be returned in the response.
     *
     * @param string|null $defaultValue
     *
     * @return string|null
     */
    public function getMetaPropertyResultName($defaultValue = null)
    {
        return $this->get(ConfigUtil::META_PROPERTY_RESULT_NAME, $defaultValue);
    }

    /**
     * Sets the name by which the meta property should be returned in the response.
     *
     * @param string $name
     */
    public function setMetaPropertyResultName($name)
    {
        if ($name) {
            $this->items[ConfigUtil::META_PROPERTY_RESULT_NAME] = $name;
        } else {
            unset($this->items[ConfigUtil::META_PROPERTY_RESULT_NAME]);
        }
    }

    /**
     * Indicates whether the path of the field value exists.
     *
     * @return bool
     */
    public function hasPropertyPath()
    {
        return $this->has(ConfigUtil::PROPERTY_PATH);
    }

    /**
     * Gets the form type.
     *
     * @return string|null
     */
    public function getFormType()
    {
        return $this->get(ConfigUtil::FORM_TYPE);
    }

    /**
     * Sets the form type.
     *
     * @param string|null $formType
     */
    public function setFormType($formType)
    {
        if ($formType) {
            $this->items[ConfigUtil::FORM_TYPE] = $formType;
        } else {
            unset($this->items[ConfigUtil::FORM_TYPE]);
        }
    }

    /**
     * Gets the form options.
     *
     * @return array|null
     */
    public function getFormOptions()
    {
        return $this->get(ConfigUtil::FORM_OPTIONS);
    }

    /**
     * Sets the form options.
     *
     * @param array|null $formOptions
     */
    public function setFormOptions($formOptions)
    {
        if ($formOptions) {
            $this->items[ConfigUtil::FORM_OPTIONS] = $formOptions;
        } else {
            unset($this->items[ConfigUtil::FORM_OPTIONS]);
        }
    }

    /**
     * Sets a form option. If an option is already exist its value will be replaced with new value.
     *
     * @param string $name  The name of an option
     * @param mixed  $value The value of an option
     */
    public function setFormOption($name, $value)
    {
        $formOptions = $this->getFormOptions();
        $formOptions[$name] = $value;
        $this->setFormOptions($formOptions);
    }

    /**
     * Gets existing validation constraints from the form options.
     *
     * @return Constraint[]|null
     */
    public function getFormConstraints()
    {
        $formOptions = $this->getFormOptions();
        if (empty($formOptions) || !\array_key_exists('constraints', $formOptions)) {
            return null;
        }

        return $formOptions['constraints'];
    }

    /**
     * Adds a validation constraint to the form options.
     *
     * @param Constraint $constraint
     */
    public function addFormConstraint(Constraint $constraint)
    {
        $formOptions = $this->getFormOptions();
        $formOptions['constraints'][] = $constraint;
        $this->setFormOptions($formOptions);
    }

    /**
     * Indicates whether at least one data transformer exists.
     *
     * @return bool
     */
    public function hasDataTransformers()
    {
        return !empty($this->items[ConfigUtil::DATA_TRANSFORMER]);
    }

    /**
     * Sets the data transformers to be applies to the field value.
     *
     * @param string|array|null $dataTransformers
     */
    public function setDataTransformers($dataTransformers)
    {
        if ($dataTransformers) {
            if (\is_string($dataTransformers)) {
                $dataTransformers = [$dataTransformers];
            }
            $this->items[ConfigUtil::DATA_TRANSFORMER] = $dataTransformers;
        } else {
            unset($this->items[ConfigUtil::DATA_TRANSFORMER]);
        }
    }

    /**
     * Gets a list of fields on which this field depends on.
     *
     * @return string[]|null
     */
    public function getDependsOn()
    {
        return $this->get(ConfigUtil::DEPENDS_ON);
    }

    /**
     * Sets a list of fields on which this field depends on.
     *
     * @param string[] $fieldNames
     */
    public function setDependsOn(array $fieldNames)
    {
        if ($fieldNames) {
            $this->items[ConfigUtil::DEPENDS_ON] = $fieldNames;
        } else {
            unset($this->items[ConfigUtil::DEPENDS_ON]);
        }
    }

    /**
     * Indicates whether the collapse target entity flag is set explicitly.
     *
     * @return bool
     */
    public function hasCollapsed()
    {
        return $this->has(ConfigUtil::COLLAPSE);
    }

    /**
     * {@inheritdoc}
     */
    public function setCollapsed($collapse = true)
    {
        $this->items[ConfigUtil::COLLAPSE] = $collapse;
    }

    /**
     * Indicates whether the target entity configuration exists.
     * This configuration makes sense only if the field represents an association with another entity.
     *
     * @return bool
     */
    public function hasTargetEntity()
    {
        return null !== $this->getTargetEntity();
    }

    /**
     * Gets the configuration of the target entity.
     * If the configuration does not exist it is created automatically.
     * Use this method only if the field represents an association with another entity.
     *
     * @return EntityDefinitionConfig
     */
    public function getOrCreateTargetEntity()
    {
        $targetEntity = $this->getTargetEntity();
        if (null === $targetEntity) {
            $targetEntity = $this->createAndSetTargetEntity();
        }

        return $targetEntity;
    }

    /**
     * Creates new instance of the target entity.
     * If the field already have the configuration of the target entity it will be overridden.
     * Use this method only if the field represents an association with another entity.
     *
     * @return EntityDefinitionConfig
     */
    public function createAndSetTargetEntity()
    {
        return $this->setTargetEntity(new EntityDefinitionConfig());
    }

    /**
     * Gets the class name of a target entity.
     *
     * @return string|null
     */
    public function getTargetClass()
    {
        return $this->get(ConfigUtil::TARGET_CLASS);
    }

    /**
     * Sets the class name of a target entity.
     *
     * @param string|null $className
     */
    public function setTargetClass($className)
    {
        if ($className) {
            $this->items[ConfigUtil::TARGET_CLASS] = $className;
        } else {
            unset($this->items[ConfigUtil::TARGET_CLASS]);
        }
    }

    /**
     * Indicates whether a target association represents "to-many" or "to-one" relationship.
     *
     * @return bool|null TRUE if a target association represents "to-many" relationship
     */
    public function isCollectionValuedAssociation()
    {
        if (!\array_key_exists(ConfigUtil::TARGET_TYPE, $this->items)) {
            return null;
        }

        return 'to-many' === $this->items[ConfigUtil::TARGET_TYPE];
    }

    /**
     * Indicates whether the type of a target association is set explicitly.
     *
     * @return bool
     */
    public function hasTargetType()
    {
        return $this->has(ConfigUtil::TARGET_TYPE);
    }

    /**
     * Gets the type of a target association.
     *
     * @return string|null Can be "to-one" or "to-many"
     */
    public function getTargetType()
    {
        return $this->get(ConfigUtil::TARGET_TYPE);
    }

    /**
     * Sets the type of a target association.
     *
     * @param string|null $targetType Can be "to-one" or "to-many"
     */
    public function setTargetType($targetType)
    {
        if ($targetType) {
            $this->items[ConfigUtil::TARGET_TYPE] = $targetType;
        } else {
            unset($this->items[ConfigUtil::TARGET_TYPE]);
        }
    }
}
