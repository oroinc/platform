<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\EntitySerializer\FieldConfigInterface;

/**
 * Represents the configuration of a field that can be used to filter data.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class FilterFieldConfig implements FieldConfigInterface
{
    private ?bool $exclude = null;
    private ?string $dataType = null;
    private array $items = [];

    /**
     * Gets a native PHP array representation of the configuration.
     */
    public function toArray(): array
    {
        $result = ConfigUtil::convertItemsToArray($this->items);
        if (true === $this->exclude) {
            $result[ConfigUtil::EXCLUDE] = $this->exclude;
        }
        if (null !== $this->dataType) {
            $result[ConfigUtil::DATA_TYPE] = $this->dataType;
        }
        if (isset($result[ConfigUtil::COLLECTION]) && false === $result[ConfigUtil::COLLECTION]) {
            unset($result[ConfigUtil::COLLECTION]);
        }
        if (isset($result[ConfigUtil::ALLOW_ARRAY]) && false === $result[ConfigUtil::ALLOW_ARRAY]) {
            unset($result[ConfigUtil::ALLOW_ARRAY]);
        }
        if (isset($result[ConfigUtil::ALLOW_RANGE]) && false === $result[ConfigUtil::ALLOW_RANGE]) {
            unset($result[ConfigUtil::ALLOW_RANGE]);
        }

        return $result;
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->items = ConfigUtil::cloneItems($this->items);
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
     * Indicates whether the exclusion flag is set explicitly.
     */
    public function hasExcluded(): bool
    {
        return null !== $this->exclude;
    }

    /**
     * Indicates whether the exclusion flag.
     */
    public function isExcluded(): bool
    {
        return $this->exclude ?? false;
    }

    /**
     * Sets the exclusion flag.
     *
     * @param bool|null $exclude The exclude flag or NULL to remove this option
     */
    public function setExcluded(?bool $exclude = true): void
    {
        $this->exclude = $exclude;
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
     * Indicates whether the path of the field value exists.
     */
    public function hasPropertyPath(): bool
    {
        return $this->has(ConfigUtil::PROPERTY_PATH);
    }

    /**
     * Gets the path of the field value.
     */
    public function getPropertyPath(string $defaultValue = null): ?string
    {
        if (empty($this->items[ConfigUtil::PROPERTY_PATH])) {
            return $defaultValue;
        }

        return $this->items[ConfigUtil::PROPERTY_PATH];
    }

    /**
     * Sets the path of the field value.
     */
    public function setPropertyPath(string $propertyPath = null): void
    {
        if ($propertyPath) {
            $this->items[ConfigUtil::PROPERTY_PATH] = $propertyPath;
        } else {
            unset($this->items[ConfigUtil::PROPERTY_PATH]);
        }
    }

    /**
     * Indicates whether the "collection" option is set explicitly.
     */
    public function hasCollection(): bool
    {
        return $this->has(ConfigUtil::COLLECTION);
    }

    /**
     * Indicates whether the filter represents a collection valued association.
     */
    public function isCollection(): bool
    {
        return (bool)$this->get(ConfigUtil::COLLECTION);
    }

    /**
     * Sets a flag indicates whether the filter represents a collection valued association.
     */
    public function setIsCollection(bool $value): void
    {
        $this->set(ConfigUtil::COLLECTION, $value);
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
     * Indicates whether the filter type is set.
     */
    public function hasType(): bool
    {
        return $this->has(ConfigUtil::FILTER_TYPE);
    }

    /**
     * Gets the filter type.
     */
    public function getType(): ?string
    {
        return $this->get(ConfigUtil::FILTER_TYPE);
    }

    /**
     * Sets the filter type.
     */
    public function setType(?string $type): void
    {
        if ($type) {
            $this->items[ConfigUtil::FILTER_TYPE] = $type;
        } else {
            unset($this->items[ConfigUtil::FILTER_TYPE]);
        }
    }

    /**
     * Gets the filter options.
     */
    public function getOptions(): ?array
    {
        return $this->get(ConfigUtil::FILTER_OPTIONS);
    }

    /**
     * Sets the filter options.
     */
    public function setOptions(?array $options): void
    {
        if ($options) {
            $this->items[ConfigUtil::FILTER_OPTIONS] = $options;
        } else {
            unset($this->items[ConfigUtil::FILTER_OPTIONS]);
        }
    }

    /**
     * Gets a list of operators supported by the filter.
     *
     * @return string[]|null
     */
    public function getOperators(): ?array
    {
        return $this->get(ConfigUtil::FILTER_OPERATORS);
    }

    /**
     * Sets a list of operators supported by the filter.
     *
     * @param string[]|null $operators
     */
    public function setOperators(?array $operators): void
    {
        if ($operators) {
            $this->items[ConfigUtil::FILTER_OPERATORS] = $operators;
        } else {
            unset($this->items[ConfigUtil::FILTER_OPERATORS]);
        }
    }

    /**
     * Indicates whether the "array allowed" flag is set explicitly.
     */
    public function hasArrayAllowed(): bool
    {
        return $this->has(ConfigUtil::ALLOW_ARRAY);
    }

    /**
     * Indicates whether the filter value can be an array.
     */
    public function isArrayAllowed(): bool
    {
        return $this->get(ConfigUtil::ALLOW_ARRAY, false);
    }

    /**
     * Sets a flag indicates whether the filter value can be an array.
     */
    public function setArrayAllowed(bool $allowArray = true): void
    {
        $this->items[ConfigUtil::ALLOW_ARRAY] = $allowArray;
    }

    /**
     * Indicates whether the "range allowed" flag is set explicitly.
     */
    public function hasRangeAllowed(): bool
    {
        return $this->has(ConfigUtil::ALLOW_RANGE);
    }

    /**
     * Indicates whether the filter value can be a pair of "from" and "to" values.
     */
    public function isRangeAllowed(): bool
    {
        return $this->get(ConfigUtil::ALLOW_RANGE, false);
    }

    /**
     * Sets a flag indicates whether the filter value can be a pair of "from" and "to" values.
     */
    public function setRangeAllowed(bool $allowRange = true): void
    {
        $this->items[ConfigUtil::ALLOW_RANGE] = $allowRange;
    }
}
