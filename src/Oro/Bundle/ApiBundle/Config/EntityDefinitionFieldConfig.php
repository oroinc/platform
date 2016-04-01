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
    use Traits\LabelTrait;
    use Traits\DescriptionTrait;
    use Traits\FormFieldTrait;

    /** a human-readable representation of the field */
    const LABEL = 'label';

    /** a human-readable description of the field */
    const DESCRIPTION = 'description';

    /** the form type that should be used for the field */
    const FORM_TYPE = 'form_type';

    /** the form options that should be used for the field */
    const FORM_OPTIONS = 'form_options';

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
