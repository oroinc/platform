<?php

namespace Oro\Bundle\ApiBundle\Config;

use Doctrine\ORM\QueryBuilder;
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
class EntityDefinitionFieldConfig extends FieldConfig
{
    private ?string $dataType = null;

    /**
     * {@inheritdoc}
     */
    public function toArray(bool $excludeTargetEntity = false): array
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
    public function isEmpty(): bool
    {
        return
            null === $this->dataType
            && parent::isEmpty();
    }

    /**
     * Sets the configuration value.
     */
    public function set(string $key, mixed $value): void
    {
        if (null !== $value) {
            $this->items[$key] = $value;
        } else {
            unset($this->items[$key]);
        }
    }

    /**
     * Indicates whether the description attribute exists.
     */
    public function hasDescription(): bool
    {
        return $this->has(ConfigUtil::DESCRIPTION);
    }

    /**
     * Gets the value of the description attribute.
     */
    public function getDescription(): string|Label|null
    {
        return $this->get(ConfigUtil::DESCRIPTION);
    }

    /**
     * Sets the value of the description attribute.
     */
    public function setDescription(string|Label|null $description): void
    {
        if ($description) {
            $this->items[ConfigUtil::DESCRIPTION] = $description;
        } else {
            unset($this->items[ConfigUtil::DESCRIPTION]);
        }
    }

    /**
     * Indicates whether the data type is set.
     */
    public function hasDataType(): bool
    {
        return null !== $this->dataType;
    }

    /**
     * Gets expected data type of the filter value.
     */
    public function getDataType(): ?string
    {
        return $this->dataType;
    }

    /**
     * Sets expected data type of the filter value.
     */
    public function setDataType(?string $dataType): void
    {
        $this->dataType = $dataType;
    }

    /**
     * Indicates whether the direction option is set explicitly.
     * If this option is not set, both the request and the response can contain this field.
     */
    public function hasDirection(): bool
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
    public function setDirection(?string $direction): void
    {
        if ($direction) {
            if (ConfigUtil::DIRECTION_INPUT_ONLY !== $direction
                && ConfigUtil::DIRECTION_OUTPUT_ONLY !== $direction
                && ConfigUtil::DIRECTION_BIDIRECTIONAL !== $direction
            ) {
                throw new \InvalidArgumentException(sprintf(
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
     */
    public function isInput(): bool
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
     */
    public function isOutput(): bool
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
     */
    public function isMetaProperty(): bool
    {
        return $this->get(ConfigUtil::META_PROPERTY, false);
    }

    /**
     * Sets a flag indicates whether the field represents a meta information.
     */
    public function setMetaProperty(bool $isMetaProperty): void
    {
        if ($isMetaProperty) {
            $this->items[ConfigUtil::META_PROPERTY] = $isMetaProperty;
        } else {
            unset($this->items[ConfigUtil::META_PROPERTY]);
        }
    }

    /**
     * Gets the name by which the meta property should be returned in the response.
     */
    public function getMetaPropertyResultName(?string $defaultValue = null): ?string
    {
        return $this->get(ConfigUtil::META_PROPERTY_RESULT_NAME, $defaultValue);
    }

    /**
     * Sets the name by which the meta property should be returned in the response.
     */
    public function setMetaPropertyResultName(?string $name): void
    {
        if ($name) {
            $this->items[ConfigUtil::META_PROPERTY_RESULT_NAME] = $name;
        } else {
            unset($this->items[ConfigUtil::META_PROPERTY_RESULT_NAME]);
        }
    }

    /**
     * Gets the form type.
     */
    public function getFormType(): ?string
    {
        return $this->get(ConfigUtil::FORM_TYPE);
    }

    /**
     * Sets the form type.
     */
    public function setFormType(?string $formType): void
    {
        if ($formType) {
            $this->items[ConfigUtil::FORM_TYPE] = $formType;
        } else {
            unset($this->items[ConfigUtil::FORM_TYPE]);
        }
    }

    /**
     * Gets the form options.
     */
    public function getFormOptions(): ?array
    {
        return $this->get(ConfigUtil::FORM_OPTIONS);
    }

    /**
     * Sets the form options.
     */
    public function setFormOptions(?array $formOptions): void
    {
        if ($formOptions) {
            $this->items[ConfigUtil::FORM_OPTIONS] = $formOptions;
        } else {
            unset($this->items[ConfigUtil::FORM_OPTIONS]);
        }
    }

    /**
     * Sets a form option. If an option is already exist its value will be replaced with new value.
     */
    public function setFormOption(string $name, mixed $value): void
    {
        $formOptions = $this->getFormOptions();
        $formOptions[$name] = $value;
        $this->setFormOptions($formOptions);
    }

    /**
     * Gets existing validation constraints from the form options.
     *
     * @return array|null [Constraint object or [constraint name or class => constraint options, ...], ...]
     */
    public function getFormConstraints(): ?array
    {
        return FormConstraintUtil::getFormConstraints($this->getFormOptions());
    }

    /**
     * Adds a validation constraint to the form options.
     */
    public function addFormConstraint(Constraint $constraint): void
    {
        $this->setFormOptions(FormConstraintUtil::addFormConstraint($this->getFormOptions(), $constraint));
    }

    /**
     * Removes a validation constraint from the form options by its class.
     */
    public function removeFormConstraint(string $constraintClass): void
    {
        $this->setFormOptions(FormConstraintUtil::removeFormConstraint($this->getFormOptions(), $constraintClass));
    }

    /**
     * Indicates whether a post processor is set.
     */
    public function hasPostProcessor(): bool
    {
        return $this->has(ConfigUtil::POST_PROCESSOR);
    }

    /**
     * Gets the type of a post processor.
     */
    public function getPostProcessor(): ?string
    {
        return $this->get(ConfigUtil::POST_PROCESSOR);
    }

    /**
     * Sets the type of a post processor.
     */
    public function setPostProcessor(?string $type): void
    {
        $this->items[ConfigUtil::POST_PROCESSOR] = $type ?: null;
    }

    /**
     * Removes a post processor.
     */
    public function removePostProcessor(): void
    {
        unset($this->items[ConfigUtil::POST_PROCESSOR]);
    }

    /**
     * Gets the options for a post processor.
     */
    public function getPostProcessorOptions(): ?array
    {
        return $this->get(ConfigUtil::POST_PROCESSOR_OPTIONS);
    }

    /**
     * Sets the options for a post processor.
     */
    public function setPostProcessorOptions(?array $options): void
    {
        if ($options) {
            $this->items[ConfigUtil::POST_PROCESSOR_OPTIONS] = $options;
        } else {
            unset($this->items[ConfigUtil::POST_PROCESSOR_OPTIONS]);
        }
    }

    /**
     * Indicates whether at least one data transformer exists.
     */
    public function hasDataTransformers(): bool
    {
        return !empty($this->items[ConfigUtil::DATA_TRANSFORMER]);
    }

    /**
     * Sets the data transformers to be applies to the field value.
     *
     * The data transformers can be the ID of a service in DIC
     * or an array of data transformers.
     * Each item of the array can be the ID of a service in DIC, an instance of
     * {@see \Oro\Component\EntitySerializer\DataTransformerInterface} or
     * {@see \Symfony\Component\Form\DataTransformerInterface},
     * or function ($value, $config, $context) : mixed.
     */
    public function setDataTransformers(string|array|null $dataTransformers): void
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
    public function getDependsOn(): ?array
    {
        return $this->get(ConfigUtil::DEPENDS_ON);
    }

    /**
     * Sets a list of fields on which this field depends on.
     *
     * @param string[] $fieldNames
     */
    public function setDependsOn(array $fieldNames): void
    {
        if ($fieldNames) {
            $this->items[ConfigUtil::DEPENDS_ON] = $fieldNames;
        } else {
            unset($this->items[ConfigUtil::DEPENDS_ON]);
        }
    }

    /**
     * Adds a field to a list of fields on which this field depends on.
     */
    public function addDependsOn(string $fieldName): void
    {
        $dependsOn = $this->getDependsOn();
        if (!$dependsOn || !\in_array($fieldName, $dependsOn, true)) {
            $dependsOn[] = $fieldName;
            $this->setDependsOn($dependsOn);
        }
    }

    /**
     * Indicates whether the collapse target entity flag is set explicitly.
     */
    public function hasCollapsed(): bool
    {
        return $this->has(ConfigUtil::COLLAPSE);
    }

    /**
     * {@inheritdoc}
     */
    public function setCollapsed(bool $collapse = true): void
    {
        $this->items[ConfigUtil::COLLAPSE] = $collapse;
    }

    /**
     * Indicates whether the target entity configuration exists.
     * This configuration makes sense only if the field represents an association with another entity.
     */
    public function hasTargetEntity(): bool
    {
        return null !== $this->getTargetEntity();
    }

    /**
     * Gets the configuration of the target entity.
     * If the configuration does not exist it is created automatically.
     * Use this method only if the field represents an association with another entity.
     */
    public function getOrCreateTargetEntity(): EntityDefinitionConfig
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
     */
    public function createAndSetTargetEntity(): EntityDefinitionConfig
    {
        return $this->setTargetEntity(new EntityDefinitionConfig());
    }

    /**
     * Gets the class name of a target entity.
     */
    public function getTargetClass(): ?string
    {
        return $this->get(ConfigUtil::TARGET_CLASS);
    }

    /**
     * Sets the class name of a target entity.
     */
    public function setTargetClass(?string $className): void
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
     * @return bool TRUE if a target association represents "to-many" relationship; otherwise, FALSE
     */
    public function isCollectionValuedAssociation(): bool
    {
        return
            \array_key_exists(ConfigUtil::TARGET_TYPE, $this->items)
            && ConfigUtil::TO_MANY === $this->items[ConfigUtil::TARGET_TYPE];
    }

    /**
     * Indicates whether the type of a target association is set explicitly.
     */
    public function hasTargetType(): bool
    {
        return $this->has(ConfigUtil::TARGET_TYPE);
    }

    /**
     * Gets the type of a target association.
     *
     * @return string|null Can be "to-one" or "to-many"
     */
    public function getTargetType(): ?string
    {
        return $this->get(ConfigUtil::TARGET_TYPE);
    }

    /**
     * Sets the type of a target association.
     *
     * @param string|null $targetType Can be "to-one" or "to-many"
     */
    public function setTargetType(?string $targetType): void
    {
        if ($targetType) {
            $this->items[ConfigUtil::TARGET_TYPE] = $targetType;
        } else {
            unset($this->items[ConfigUtil::TARGET_TYPE]);
        }
    }

    /**
     * Gets ORM query builder for a query that should be used to load data if the field is an association.
     */
    public function getAssociationQuery(): ?QueryBuilder
    {
        return $this->items[ConfigUtil::ASSOCIATION_QUERY] ?? null;
    }

    /**
     * Sets ORM query builder for a query that should be used to load data if the field is an association.
     *
     * IMPORTANT: the query builder must follow the rules described in AssociationQuery class.
     * @see \Oro\Component\EntitySerializer\AssociationQuery
     */
    public function setAssociationQuery(QueryBuilder $query = null): void
    {
        if (null === $query) {
            unset($this->items[ConfigUtil::ASSOCIATION_QUERY]);
        } else {
            if (!$this->getTargetClass()) {
                throw new \InvalidArgumentException(
                    'The target class must be specified to be able to use an association query.'
                );
            }
            $this->items[ConfigUtil::ASSOCIATION_QUERY] = $query;
        }
    }
}
