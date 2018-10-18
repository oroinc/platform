<?php

namespace Oro\Bundle\ApiBundle\Config;

/**
 * An interface for configuration sections that are a container for fields.
 */
interface EntityConfigInterface extends ConfigBagInterface
{
    /**
     * Indicates whether the configuration of at least one field exists.
     *
     * @return bool
     */
    public function hasFields();

    /**
     * Gets the configuration for all fields.
     *
     * @return FieldConfigInterface[] [field name => config, ...]
     */
    public function getFields();

    /**
     * Indicates whether the field configuration exists.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasField($fieldName);

    /**
     * Gets the configuration of the field.
     *
     * @param string $fieldName
     *
     * @return FieldConfigInterface|null
     */
    public function getField($fieldName);

    /**
     * Finds a field by its name or property path.
     * If $findByPropertyPath equals to TRUE do the find using a given field name as a property path.
     *
     * @param string $fieldName
     * @param bool   $findByPropertyPath
     *
     * @return FieldConfigInterface|null
     */
    public function findField($fieldName, $findByPropertyPath = false);

    /**
     * Finds the name of a field by its property path.
     *
     * @param string $propertyPath
     *
     * @return string|null
     */
    public function findFieldNameByPropertyPath($propertyPath);

    /**
     * Gets the configuration of existing field or adds new field with a given name.
     *
     * @param string $fieldName
     *
     * @return FieldConfigInterface
     */
    public function getOrAddField($fieldName);

    /**
     * Adds the configuration of the field.
     *
     * @param string                    $fieldName
     * @param FieldConfigInterface|null $field
     *
     * @return FieldConfigInterface
     */
    public function addField($fieldName, $field = null);

    /**
     * Removes the configuration of the field.
     *
     * @param string $fieldName
     */
    public function removeField($fieldName);

    /**
     * Indicates whether the exclusion policy is set explicitly.
     *
     * @return bool
     */
    public function hasExclusionPolicy();

    /**
     * Gets the exclusion strategy that should be used for the entity.
     *
     * @return string An exclusion strategy, e.g. "none" or "all"
     */
    public function getExclusionPolicy();

    /**
     * Sets the exclusion strategy that should be used for the entity.
     *
     * @param string|null $exclusionPolicy An exclusion strategy, e.g. "none" or "all",
     *                                     or NULL to remove this option
     */
    public function setExclusionPolicy($exclusionPolicy);

    /**
     * Indicates whether all fields are not configured explicitly should be excluded.
     *
     * @return bool
     */
    public function isExcludeAll();

    /**
     * Sets the exclusion strategy to exclude all fields are not configured explicitly.
     */
    public function setExcludeAll();

    /**
     * Sets the exclusion strategy to exclude only fields are marked as excluded.
     */
    public function setExcludeNone();
}
