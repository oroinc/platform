<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\EntitySerializer\EntityConfigInterface;
use Oro\Component\EntitySerializer\FieldConfigInterface;
use Oro\Component\EntitySerializer\FindFieldUtil;

/**
 * Represents the configuration of all fields that can be used to sort data.
 */
class SortersConfig implements EntityConfigInterface
{
    private ?string $exclusionPolicy = null;
    private array $items = [];
    /** @var SorterFieldConfig[] */
    private array $fields = [];

    /**
     * Gets a native PHP array representation of the configuration.
     */
    public function toArray(): array
    {
        $result = ConfigUtil::convertItemsToArray($this->items);
        if (null !== $this->exclusionPolicy && ConfigUtil::EXCLUSION_POLICY_NONE !== $this->exclusionPolicy) {
            $result[ConfigUtil::EXCLUSION_POLICY] = $this->exclusionPolicy;
        }
        $fields = ConfigUtil::convertObjectsToArray($this->fields, true);
        if ($fields) {
            $result[ConfigUtil::FIELDS] = $fields;
        }

        return $result;
    }

    /**
     * Indicates whether the entity does not have a configuration.
     */
    public function isEmpty(): bool
    {
        return
            null === $this->exclusionPolicy
            && empty($this->items)
            && empty($this->fields);
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->items = ConfigUtil::cloneItems($this->items);
        $this->fields = ConfigUtil::cloneObjects($this->fields);
    }

    /**
     * Indicates whether the configuration of at least one sorter exists.
     */
    public function hasFields(): bool
    {
        return !empty($this->fields);
    }

    /**
     * Gets the configuration for all sorters.
     *
     * @return SorterFieldConfig[] [field name => config, ...]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Indicates whether the configuration of the sorter exists.
     */
    public function hasField(string $fieldName): bool
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * Gets the configuration of the sorter.
     */
    public function getField(string $fieldName): ?SorterFieldConfig
    {
        return $this->fields[$fieldName] ?? null;
    }

    /**
     * Finds the configuration of the sorter by its name or property path.
     * If $findByPropertyPath equals to TRUE do the find using a given field name as a property path.
     */
    public function findField(string $fieldName, bool $findByPropertyPath = false): ?SorterFieldConfig
    {
        return FindFieldUtil::doFindField($this->fields, $fieldName, $findByPropertyPath);
    }

    /**
     * Finds the name of the sorter by its property path.
     */
    public function findFieldNameByPropertyPath(string $propertyPath): ?string
    {
        return FindFieldUtil::doFindFieldNameByPropertyPath($this->fields, $propertyPath);
    }

    /**
     * Gets the configuration of existing sorter or adds new sorter for a given field.
     */
    public function getOrAddField(string $fieldName): SorterFieldConfig
    {
        $field = $this->getField($fieldName);
        if (null === $field) {
            $field = $this->addField($fieldName);
        }

        return $field;
    }

    /**
     * Adds the configuration of the sorter.
     */
    public function addField(string $fieldName, FieldConfigInterface $field = null): SorterFieldConfig
    {
        if (null === $field) {
            $field = new SorterFieldConfig();
        }

        $this->fields[$fieldName] = $field;

        return $field;
    }

    /**
     * Removes the configuration of the sorter.
     */
    public function removeField(string $fieldName): void
    {
        unset($this->fields[$fieldName]);
    }

    /**
     * Indicates whether the configuration attribute exists.
     */
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->items);
    }

    /**
     * Gets the configuration value.
     */
    public function get(string $key, mixed $defaultValue = null): mixed
    {
        if (!\array_key_exists($key, $this->items)) {
            return $defaultValue;
        }

        return $this->items[$key];
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
     * Removes the configuration value.
     */
    public function remove(string $key): void
    {
        unset($this->items[$key]);
    }

    /**
     * Gets names of all configuration attributes.
     *
     * @return string[]
     */
    public function keys(): array
    {
        return array_keys($this->items);
    }

    /**
     * Indicates whether the exclusion policy is set explicitly.
     */
    public function hasExclusionPolicy(): bool
    {
        return null !== $this->exclusionPolicy;
    }

    /**
     * Gets the exclusion strategy that should be used for the entity.
     *
     * @return string An exclusion strategy, e.g. "none" or "all"
     */
    public function getExclusionPolicy(): string
    {
        return $this->exclusionPolicy ?? ConfigUtil::EXCLUSION_POLICY_NONE;
    }

    /**
     * Sets the exclusion strategy that should be used for the entity.
     *
     * @param string|null $exclusionPolicy An exclusion strategy, e.g. "none" or "all",
     *                                     or NULL to remove this option
     */
    public function setExclusionPolicy(?string $exclusionPolicy): void
    {
        $this->exclusionPolicy = $exclusionPolicy;
    }

    /**
     * Indicates whether all fields are not configured explicitly should be excluded.
     */
    public function isExcludeAll(): bool
    {
        return ConfigUtil::EXCLUSION_POLICY_ALL === $this->exclusionPolicy;
    }

    /**
     * Sets the exclusion strategy to exclude all fields are not configured explicitly.
     */
    public function setExcludeAll(): void
    {
        $this->exclusionPolicy = ConfigUtil::EXCLUSION_POLICY_ALL;
    }

    /**
     * Sets the exclusion strategy to exclude only fields are marked as excluded.
     */
    public function setExcludeNone(): void
    {
        $this->exclusionPolicy = ConfigUtil::EXCLUSION_POLICY_NONE;
    }
}
