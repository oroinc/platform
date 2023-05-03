<?php

namespace Oro\Component\EntitySerializer;

/**
 * An interface for configuration sections that represent a field.
 */
interface FieldConfigInterface
{
    /**
     * Indicates whether the exclusion flag is set explicitly.
     */
    public function hasExcluded(): bool;

    /**
     * Indicates whether the field should be excluded.
     */
    public function isExcluded(): bool;

    /**
     * Sets a flag indicates whether the field should be excluded.
     *
     * @param bool|null $exclude The exclude flag or NULL to remove this option
     */
    public function setExcluded(?bool $exclude = true): void;

    /**
     * Indicates whether the path of the field value exists.
     */
    public function hasPropertyPath(): bool;

    /**
     * Gets the path of the field value.
     */
    public function getPropertyPath(string $defaultValue = null): ?string;

    /**
     * Sets the path of the field value.
     */
    public function setPropertyPath(string $propertyPath = null): void;
}
