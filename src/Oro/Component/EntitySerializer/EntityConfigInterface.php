<?php

namespace Oro\Component\EntitySerializer;

/**
 * An interface for configuration sections that are a container for fields.
 */
interface EntityConfigInterface
{
    /**
     * Indicates whether the configuration of at least one field exists.
     */
    public function hasFields(): bool;

    /**
     * Gets the configuration for all fields.
     *
     * @return FieldConfigInterface[] [field name => config, ...]
     */
    public function getFields(): array;

    /**
     * Indicates whether the field configuration exists.
     */
    public function hasField(string $fieldName): bool;

    /**
     * Gets the configuration of the field.
     */
    public function getField(string $fieldName): ?FieldConfigInterface;

    /**
     * Finds a field by its name or property path.
     * If $findByPropertyPath equals to TRUE do the find using a given field name as a property path.
     */
    public function findField(string $fieldName, bool $findByPropertyPath = false): ?FieldConfigInterface;

    /**
     * Finds the name of a field by its property path.
     */
    public function findFieldNameByPropertyPath(string $propertyPath): ?string;

    /**
     * Gets the configuration of existing field or adds new field with a given name.
     */
    public function getOrAddField(string $fieldName): FieldConfigInterface;

    /**
     * Adds the configuration of the field.
     */
    public function addField(string $fieldName, FieldConfigInterface $field = null): FieldConfigInterface;

    /**
     * Removes the configuration of the field.
     */
    public function removeField(string $fieldName): void;

    /**
     * Indicates whether the exclusion policy is set explicitly.
     */
    public function hasExclusionPolicy(): bool;

    /**
     * Gets the exclusion strategy that should be used for the entity.
     *
     * @return string An exclusion strategy, e.g. "none" or "all"
     */
    public function getExclusionPolicy(): string;

    /**
     * Sets the exclusion strategy that should be used for the entity.
     *
     * @param string|null $exclusionPolicy An exclusion strategy, e.g. "none" or "all",
     *                                     or NULL to remove this option
     */
    public function setExclusionPolicy(?string $exclusionPolicy): void;

    /**
     * Indicates whether all fields are not configured explicitly should be excluded.
     */
    public function isExcludeAll(): bool;

    /**
     * Sets the exclusion strategy to exclude all fields are not configured explicitly.
     */
    public function setExcludeAll(): void;

    /**
     * Sets the exclusion strategy to exclude only fields are marked as excluded.
     */
    public function setExcludeNone(): void;
}
