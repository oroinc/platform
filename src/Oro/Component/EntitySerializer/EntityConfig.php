<?php

namespace Oro\Component\EntitySerializer;

/**
 * Represents the configuration of an entity.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityConfig implements EntityConfigInterface
{
    protected ?string $exclusionPolicy = null;
    protected array $items = [];
    /** @var FieldConfig[] */
    protected array $fields = [];

    /**
     * Gets a native PHP array representation of the entity configuration.
     */
    public function toArray(): array
    {
        $result = $this->items;
        if (null !== $this->exclusionPolicy && ConfigUtil::EXCLUSION_POLICY_NONE !== $this->exclusionPolicy) {
            $result[ConfigUtil::EXCLUSION_POLICY] = $this->exclusionPolicy;
        }
        if (!empty($this->fields)) {
            foreach ($this->fields as $fieldName => $fieldConfig) {
                $result[ConfigUtil::FIELDS][$fieldName] = $fieldConfig->toArray();
            }
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
     * Make a deep copy of object.
     */
    public function __clone()
    {
        $this->items = ConfigUtil::cloneItems($this->items);
        $this->fields = ConfigUtil::cloneObjects($this->fields);
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
     * Indicates whether the configuration of at least one field exists.
     */
    public function hasFields(): bool
    {
        return !empty($this->fields);
    }

    /**
     * Gets the configuration for all fields.
     *
     * @return FieldConfig[] [field name => config, ...]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Checks whether the configuration of a field exists.
     */
    public function hasField(string $fieldName): bool
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * Gets the configuration of a field.
     */
    public function getField(string $fieldName): ?FieldConfig
    {
        return $this->fields[$fieldName] ?? null;
    }

    /**
     * Gets the configuration of existing field or adds new field with a given name.
     */
    public function getOrAddField(string $fieldName): FieldConfig
    {
        $field = $this->getField($fieldName);
        if (null === $field) {
            $field = $this->addField($fieldName);
        }

        return $field;
    }

    /**
     * Finds the configuration of the field by its name or property path.
     * If $findByPropertyPath equals to TRUE do the find using a given field name as a property path.
     */
    public function findField(string $fieldName, bool $findByPropertyPath = false): ?FieldConfig
    {
        return FindFieldUtil::doFindField($this->fields, $fieldName, $findByPropertyPath);
    }

    /**
     * Finds the name of the field by its property path.
     * This method can be useful when a field was renamed and you need to find
     * the name of the result field by the name defined in an entity.
     */
    public function findFieldNameByPropertyPath(string $propertyPath): ?string
    {
        return FindFieldUtil::doFindFieldNameByPropertyPath($this->fields, $propertyPath);
    }

    /**
     * Adds the configuration of a field.
     */
    public function addField(string $fieldName, ?FieldConfigInterface $field = null): FieldConfig
    {
        if (null === $field) {
            $field = new FieldConfig();
        }

        $this->fields[$fieldName] = $field;

        return $field;
    }

    /**
     * Removes the configuration of a field.
     */
    public function removeField(string $fieldName): void
    {
        unset($this->fields[$fieldName]);
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

    /**
     * Indicates whether using of Doctrine partial objects is enabled.
     */
    public function isPartialLoadEnabled(): bool
    {
        return !$this->get(ConfigUtil::DISABLE_PARTIAL_LOAD, false);
    }

    /**
     * Allows using of Doctrine partial objects.
     */
    public function enablePartialLoad(): void
    {
        unset($this->items[ConfigUtil::DISABLE_PARTIAL_LOAD]);
    }

    /**
     * Prohibits using of Doctrine partial objects.
     */
    public function disablePartialLoad(): void
    {
        $this->items[ConfigUtil::DISABLE_PARTIAL_LOAD] = true;
    }

    /**
     * Gets the ordering of the result.
     * The direction can be "ASC" or "DESC".
     * The Doctrine\Common\Collections\Criteria::ASC and Doctrine\Common\Collections\Criteria::DESC constants
     * can be used.
     *
     * @return array [field name => direction, ...]
     */
    public function getOrderBy(): array
    {
        return $this->get(ConfigUtil::ORDER_BY, []);
    }

    /**
     * Sets the ordering of the result.
     * The direction can be "ASC" or "DESC".
     * The Doctrine\Common\Collections\Criteria::ASC and Doctrine\Common\Collections\Criteria::DESC constants
     * can be used.
     *
     * @param array $orderBy [field name => direction, ...]
     */
    public function setOrderBy(array $orderBy): void
    {
        if ($orderBy) {
            $this->items[ConfigUtil::ORDER_BY] = $orderBy;
        } else {
            unset($this->items[ConfigUtil::ORDER_BY]);
        }
    }

    /**
     * Gets the maximum number of items in the result.
     *
     * @return int|null The requested maximum number of items or NULL if not limited
     */
    public function getMaxResults(): ?int
    {
        return $this->get(ConfigUtil::MAX_RESULTS);
    }

    /**
     * Sets the maximum number of items in the result.
     *
     * @param int|null $maxResults The maximum number of items or NULL to set unlimited
     */
    public function setMaxResults(?int $maxResults): void
    {
        if (null === $maxResults || $maxResults < 0) {
            unset($this->items[ConfigUtil::MAX_RESULTS]);
        } else {
            $this->items[ConfigUtil::MAX_RESULTS] = $maxResults;
        }
    }

    /**
     * Indicates whether an additional element with
     * key "_" {@see \Oro\Component\EntitySerializer\ConfigUtil::INFO_RECORD_KEY}
     * and value ['has_more' => true] {@see \Oro\Component\EntitySerializer\ConfigUtil::HAS_MORE}
     * should be added to a collection if it has more records than it was requested.
     */
    public function getHasMore(): bool
    {
        return (bool)$this->get(ConfigUtil::HAS_MORE, false);
    }

    /**
     * Set a flag indicates whether an additional element with
     * key "_" {@see \Oro\Component\EntitySerializer\ConfigUtil::INFO_RECORD_KEY}
     * and value ['has_more' => true] {@see \Oro\Component\EntitySerializer\ConfigUtil::HAS_MORE}
     * should be added to a collection if it has more records than it was requested.
     */
    public function setHasMore(bool $hasMore): void
    {
        if ($hasMore) {
            $this->items[ConfigUtil::HAS_MORE] = true;
        } else {
            unset($this->items[ConfigUtil::HAS_MORE]);
        }
    }

    /**
     * Gets Doctrine query hints.
     * Each hint can be a string or an associative array with "name" and "value" keys.
     */
    public function getHints(): array
    {
        return $this->get(ConfigUtil::HINTS, []);
    }

    /**
     * Adds Doctrine query hint.
     */
    public function addHint(string $name, mixed $value = null): void
    {
        $hints = $this->getHints();
        if (null === $value) {
            $hints[] = $name;
        } else {
            $hints[] = ['name' => $name, 'value' => $value];
        }

        $this->items[ConfigUtil::HINTS] = $hints;
    }

    /**
     * Adds Doctrine query hint.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function removeHint(string $name, mixed $value = null): void
    {
        $hints = $this->getHints();
        $toRemove = [];
        if (null === $value) {
            foreach ($hints as $key => $hint) {
                if (\is_string($hint) && $hint === $name) {
                    $toRemove[] = $key;
                }
            }
        } else {
            foreach ($hints as $key => $hint) {
                if (\is_array($hint) && $hint['name'] === $name && $hint['value'] === $value) {
                    $toRemove[] = $key;
                }
            }
        }

        if (!empty($toRemove)) {
            foreach ($toRemove as $key) {
                unset($hints[$key]);
            }
            if ($hints) {
                $this->items[ConfigUtil::HINTS] = \array_values($hints);
            } else {
                unset($this->items[ConfigUtil::HINTS]);
            }
        }
    }

    /**
     * Gets a list of associations for which INNER JOIN should be used instead of LEFT JOIN.
     *
     * @return string[] [property path, ...]
     */
    public function getInnerJoinAssociations(): array
    {
        return $this->get(ConfigUtil::INNER_JOIN_ASSOCIATIONS, []);
    }

    /**
     * Sets a list of associations for which INNER JOIN should be used instead of LEFT JOIN.
     *
     * @param string[] $propertyPaths [property path, ...]
     */
    public function setInnerJoinAssociations(array $propertyPaths): void
    {
        if ($propertyPaths) {
            $this->items[ConfigUtil::INNER_JOIN_ASSOCIATIONS] = $propertyPaths;
        } else {
            unset($this->items[ConfigUtil::INNER_JOIN_ASSOCIATIONS]);
        }
    }

    /**
     * Adds an association to a list of associations for which INNER JOIN should be used instead of LEFT JOIN.
     */
    public function addInnerJoinAssociation(string $propertyPath): void
    {
        $propertyPaths = $this->get(ConfigUtil::INNER_JOIN_ASSOCIATIONS, []);
        if (!$propertyPaths || !\in_array($propertyPath, $propertyPaths, true)) {
            $propertyPaths[] = $propertyPath;
            $this->items[ConfigUtil::INNER_JOIN_ASSOCIATIONS] = $propertyPaths;
        }
    }

    /**
     * Removes an association from a list of associations for which INNER JOIN should be used instead of LEFT JOIN.
     */
    public function removeInnerJoinAssociation(string $propertyPath): void
    {
        $propertyPaths = $this->get(ConfigUtil::INNER_JOIN_ASSOCIATIONS, []);
        if ($propertyPaths) {
            $i = array_search($propertyPath, $propertyPaths, true);
            if (false !== $i) {
                unset($propertyPaths[$i]);
                $this->setInnerJoinAssociations(array_values($propertyPaths));
            }
        }
    }

    /**
     * Gets a handler that should be used to modify serialized data for a single item.
     *
     * @return callable|null function (array $item, array $context) : array
     */
    public function getPostSerializeHandler(): ?callable
    {
        return $this->get(ConfigUtil::POST_SERIALIZE);
    }

    /**
     * Sets a handler that should be used to modify serialized data for a single item.
     *
     * @param callable|null $handler function (array $item, array $context) : array
     */
    public function setPostSerializeHandler(?callable $handler): void
    {
        if (null !== $handler) {
            $this->items[ConfigUtil::POST_SERIALIZE] = $handler;
        } else {
            unset($this->items[ConfigUtil::POST_SERIALIZE]);
        }
    }

    /**
     * Gets a handler that should be used to modify serialized data for a list of items.
     *
     * @return callable|null function (array $items, array $context) : array
     */
    public function getPostSerializeCollectionHandler(): ?callable
    {
        return $this->get(ConfigUtil::POST_SERIALIZE_COLLECTION);
    }

    /**
     * Sets a handler that should be used to modify serialized data for a list of items.
     * This handler is executed after each element in the collection
     * is processed by own post serialization handler.
     * @see setPostSerializeHandler
     * IMPORTANT: the items are an associative array and the collection handler must keep
     * keys in this array without changes.
     *
     * @param callable|null $handler function (array $items, array $context) : array
     */
    public function setPostSerializeCollectionHandler(?callable $handler): void
    {
        if (null !== $handler) {
            $this->items[ConfigUtil::POST_SERIALIZE_COLLECTION] = $handler;
        } else {
            unset($this->items[ConfigUtil::POST_SERIALIZE_COLLECTION]);
        }
    }
}
