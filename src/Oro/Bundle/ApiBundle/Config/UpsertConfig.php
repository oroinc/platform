<?php

namespace Oro\Bundle\ApiBundle\Config;

/**
 * Represents the configuration of the upsert operation.
 * @link https://doc.oroinc.com/api/upsert-operation/
 */
class UpsertConfig
{
    private const ID_FIELD = 'id';

    private ?bool $enabled = null;
    private ?bool $allowedById = null;
    /** @var array [[field name, ...], ...] */
    private array $fields = [];
    private bool $replace = false;

    /**
     * Gets a native PHP array representation of the upsert operation configuration.
     */
    public function toArray(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $upsertFields = $this->getFields();
        if ($this->isAllowedById()) {
            $upsertFields = array_merge([[self::ID_FIELD]], $upsertFields);
        }

        return $upsertFields;
    }

    /**
     * Indicates whether the upsert operation is enabled or disabled explicitly.
     */
    public function hasEnabled(): bool
    {
        return null !== $this->enabled;
    }

    /**
     * Indicates whether the upsert operation is enabled or disabled.
     */
    public function isEnabled(): bool
    {
        return false !== $this->enabled;
    }

    /**
     * Enables or disables the upsert operation.
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Indicates whether a flag that indicates whether the upsert operation can use entity identifier
     * to find an entity is set explicitly.
     */
    public function hasAllowedById(): bool
    {
        return null !== $this->allowedById;
    }

    /**
     * Indicates whether the upsert operation can use entity identifier to find an entity.
     */
    public function isAllowedById(): bool
    {
        return true === $this->allowedById;
    }

    /**
     * Sets a flag that indicates whether the upsert operation can use entity identifier to find an entity.
     */
    public function setAllowedById(bool $allowedById): void
    {
        $this->allowedById = $allowedById;
    }

    /**
     * Checks whether the upsert operation can use the given group field names to find an entity.
     */
    public function isAllowedFields(array $fieldNames): bool
    {
        sort($fieldNames, SORT_STRING);

        return \in_array($fieldNames, $this->fields, true);
    }

    /**
     * Gets groups of field names by which an entity can be identified.
     *
     * @return array [[field name, ...], ...]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Indicates whether replacing all groups of field names by which an entity can be identified was requested.
     */
    public function isReplaceFields(): bool
    {
        return $this->replace;
    }

    /**
     * Completely replaces all groups of field names by which an entity can be identified.
     */
    public function replaceFields(array $fields): void
    {
        $this->replace = false;
        $this->fields = [];
        foreach ($fields as $fieldNames) {
            $this->addFields($fieldNames);
        }
        $this->replace = true;
    }

    /**
     * Adds the given group of field names to groups of field names by which an entity can be identified.
     */
    public function addFields(array $fieldNames): void
    {
        if ($this->replace) {
            return;
        }

        if (\count($fieldNames) === 1 && self::ID_FIELD === reset($fieldNames)) {
            $this->setAllowedById(true);
        } else {
            sort($fieldNames, SORT_STRING);
            $foundIndex = self::searchFields($this->fields, $fieldNames);
            if (null === $foundIndex) {
                $this->fields[] = $fieldNames;
            }
        }
    }

    /**
     * Removes the given group of field names from groups of field names by which an entity can be identified.
     */
    public function removeFields(array $fieldNames): void
    {
        if ($this->replace) {
            return;
        }

        if (\count($fieldNames) === 1 && self::ID_FIELD === reset($fieldNames)) {
            $this->setAllowedById(false);
        } else {
            sort($fieldNames, SORT_STRING);
            $foundIndex = self::searchFields($this->fields, $fieldNames);
            if (null !== $foundIndex) {
                unset($this->fields[$foundIndex]);
                $this->fields = array_values($this->fields);
            }
        }
    }

    private static function searchFields(array $fields, array $fieldNames): ?int
    {
        $key = implode(':', $fieldNames);
        foreach ($fields as $i => $v) {
            if (implode(':', $v) === $key) {
                return $i;
            }
        }

        return null;
    }
}
