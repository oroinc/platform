<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Component\EntitySerializer\FieldConfig;

/**
 * @method EntityDefinitionConfig|null getTargetEntity()
 * @method EntityDefinitionConfig|null setTargetEntity(EntityDefinitionConfig $targetEntity = null)
 */
class EntityDefinitionFieldConfig extends FieldConfig implements FieldConfigInterface
{
    use Traits\ConfigTrait;
    use Traits\ExcludeTrait;
    use Traits\DescriptionTrait;
    use Traits\DataTypeTrait;
    use Traits\AssociationTargetTrait;
    use Traits\FormTrait;

    /** a human-readable description of the field */
    const DESCRIPTION = EntityDefinitionConfig::DESCRIPTION;

    /** the data type of the field value */
    const DATA_TYPE = 'data_type';

    /** the class name of a target entity */
    const TARGET_CLASS = 'target_class';

    /**
     * the type of a target association, can be "to-one" or "to-many",
     * also "collection" can be used in Resources/config/oro/api.yml file as an alias for "to-many"
     */
    const TARGET_TYPE = 'target_type';

    /** the form type that should be used for the field */
    const FORM_TYPE = EntityDefinitionConfig::FORM_TYPE;

    /** the form options that should be used for the field */
    const FORM_OPTIONS = EntityDefinitionConfig::FORM_OPTIONS;

    /**
     * {@inheritdoc}
     */
    public function toArray($excludeTargetEntity = false)
    {
        $result = parent::toArray($excludeTargetEntity);
        $this->removeItemWithDefaultValue($result, self::EXCLUDE);
        $this->removeItemWithDefaultValue($result, self::COLLAPSE);

        return $result;
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
     * Indicates whether the collapse target entity flag is set explicitly.
     *
     * @return bool
     */
    public function hasCollapsed()
    {
        return array_key_exists(self::COLLAPSE, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function setCollapsed($collapse = true)
    {
        $this->items[self::COLLAPSE] = $collapse;
    }

    /**
     * Indicates whether the path of the field value exists.
     *
     * @return string
     */
    public function hasPropertyPath()
    {
        return array_key_exists(self::PROPERTY_PATH, $this->items);
    }

    /**
     * Whether at least one data transformer exists.
     *
     * @return bool
     */
    public function hasDataTransformers()
    {
        return !empty($this->items[self::DATA_TRANSFORMER]);
    }

    /**
     * Sets the data transformers to be applies to the field value.
     *
     * @param string|array|null $dataTransformers
     */
    public function setDataTransformers($dataTransformers)
    {
        if (empty($dataTransformers)) {
            unset($this->items[self::DATA_TRANSFORMER]);
        } else {
            if (is_string($dataTransformers)) {
                $dataTransformers = [$dataTransformers];
            }
            $this->items[self::DATA_TRANSFORMER] = $dataTransformers;
        }
    }
}
