<?php

namespace Oro\Component\EntitySerializer;

use Symfony\Component\Form\DataTransformerInterface as FormDataTransformerInterface;

/**
 * Represents the configuration of an entity field.
 */
class FieldConfig implements FieldConfigInterface
{
    protected ?bool $exclude = null;
    protected array $items = [];
    private ?EntityConfig $targetEntity = null;

    /**
     * Gets a native PHP array representation of the field configuration.
     */
    public function toArray(bool $excludeTargetEntity = false): array
    {
        $result = $this->items;
        if (true === $this->exclude) {
            $result[ConfigUtil::EXCLUDE] = $this->exclude;
        }
        if (!$excludeTargetEntity && null !== $this->targetEntity) {
            $result = \array_merge($result, $this->targetEntity->toArray());
        }

        return $result;
    }

    /**
     * Indicates whether the field does not have a configuration.
     */
    public function isEmpty(): bool
    {
        return
            null === $this->exclude
            && empty($this->items)
            && (null === $this->targetEntity || $this->targetEntity->isEmpty());
    }

    /**
     * Make a deep copy of object.
     */
    public function __clone()
    {
        $this->items = ConfigUtil::cloneItems($this->items);
        if (null !== $this->targetEntity) {
            $this->targetEntity = clone $this->targetEntity;
        }
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
        $this->items[$key] = $value;
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
     * Gets the configuration of the target entity if the field represents an association with another entity.
     */
    public function getTargetEntity(): ?EntityConfig
    {
        return $this->targetEntity;
    }

    /**
     * Sets the configuration of the target entity.
     * Use this method only if the field represents an association with another entity.
     */
    public function setTargetEntity(EntityConfig $targetEntity = null): ?EntityConfig
    {
        $this->targetEntity = $targetEntity;

        return $this->targetEntity;
    }

    /**
     * Indicates whether the exclusion flag is set explicitly.
     */
    public function hasExcluded(): bool
    {
        return null !== $this->exclude;
    }

    /**
     * Indicates whether the field should be excluded.
     */
    public function isExcluded(): bool
    {
        return $this->exclude ?? false;
    }

    /**
     * Sets a flag indicates whether the field should be excluded.
     *
     * @param bool|null $exclude The exclude flag or NULL to remove this option
     */
    public function setExcluded(?bool $exclude = true): void
    {
        $this->exclude = $exclude;
    }

    /**
     * Indicates whether the target entity should be collapsed.
     */
    public function isCollapsed(): bool
    {
        if (!\array_key_exists(ConfigUtil::COLLAPSE, $this->items)) {
            return false;
        }

        return $this->items[ConfigUtil::COLLAPSE];
    }

    /**
     * Sets a flag indicates whether the target entity should be collapsed.
     */
    public function setCollapsed(bool $collapse = true): void
    {
        if ($collapse) {
            $this->items[ConfigUtil::COLLAPSE] = $collapse;
        } else {
            unset($this->items[ConfigUtil::COLLAPSE]);
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
     * Gets a list of data transformers that should be applied to the field value.
     * Please note that these data transformers work only during loading of data
     * and they are applicable only to fields. For associations they do not work.
     *
     * @return array Each item of the array can be the ID of a service in DIC, an instance of
     *               {@see \Oro\Component\EntitySerializer\DataTransformerInterface} or
     *               {@see \Symfony\Component\Form\DataTransformerInterface},
     *               or function ($value, $config, $context) : mixed.
     */
    public function getDataTransformers(): array
    {
        if (!\array_key_exists(ConfigUtil::DATA_TRANSFORMER, $this->items)) {
            return [];
        }

        return $this->items[ConfigUtil::DATA_TRANSFORMER];
    }

    /**
     * Adds the data transformer to be applies to the field value.
     *
     * The data transformer can be the ID of a service in DIC or an instance of
     * {@see \Oro\Component\EntitySerializer\DataTransformerInterface} or
     * {@see \Symfony\Component\Form\DataTransformerInterface},
     * or function ($value, $config, $context) : mixed.
     *
     * Please note that these data transformers work only during loading of data
     * and they are applicable only to fields. For associations they do not work.
     */
    public function addDataTransformer(
        string|callable|DataTransformerInterface|FormDataTransformerInterface $dataTransformer
    ): void {
        $transformers = $this->getDataTransformers();
        $transformers[] = $dataTransformer;
        $this->items[ConfigUtil::DATA_TRANSFORMER] = $transformers;
    }
}
