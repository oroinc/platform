<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\FieldConfig;

/**
 * @method EntityDefinitionFieldConfig[] getFields()
 * @method EntityDefinitionFieldConfig|null getField($fieldName)
 */
class EntityDefinitionConfig extends EntityConfig implements EntityConfigInterface
{
    use Traits\ConfigTrait;
    use Traits\LabelTrait;
    use Traits\PluralLabelTrait;
    use Traits\DescriptionTrait;

    /** a human-readable representation of the entity */
    const LABEL = 'label';

    /** a human-readable representation in plural of the entity */
    const PLURAL_LABEL = 'plural_label';

    /** a human-readable description of the entity */
    const DESCRIPTION = 'description';

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $result = parent::toArray();
        $this->removeItemWithDefaultValue($result, self::EXCLUSION_POLICY, self::EXCLUSION_POLICY_NONE);
        $this->removeItemWithDefaultValue($result, self::DISABLE_PARTIAL_LOAD);
        $this->removeItemWithDefaultValue($result, FieldConfig::COLLAPSE);

        $keys = array_keys($result);
        foreach ($keys as $key) {
            $value = $result[$key];
            if (is_object($value) && method_exists($value, 'toArray')) {
                $result[$key] = $value->toArray();
            }
        }

        if (isset($result[self::FIELDS])) {
            $fieldNames = array_keys($result[self::FIELDS]);
            foreach ($fieldNames as $fieldName) {
                if (empty($result[self::FIELDS][$fieldName])) {
                    $result[self::FIELDS][$fieldName] = null;
                }
            }
        }

        return $result;
    }

    /**
     * Checks whether the configuration of at least one field exists.
     *
     * @return bool
     */
    public function hasFields()
    {
        $fields = $this->getFields();

        return !empty($fields);
    }

    /**
     * Gets the configuration of existing field or adds new field with a given name.
     *
     * @param string $fieldName
     *
     * @return EntityDefinitionFieldConfig
     */
    public function getOrAddField($fieldName)
    {
        $field = $this->getField($fieldName);
        if (null === $field) {
            $field = $this->addField($fieldName);
        }

        return $field;
    }

    /**
     * Adds the configuration of a field.
     *
     * @param string                           $fieldName
     * @param EntityDefinitionFieldConfig|null $field
     *
     * @return EntityDefinitionFieldConfig
     */
    public function addField($fieldName, $field = null)
    {
        if (null === $field) {
            $field = new EntityDefinitionFieldConfig();
        }

        return parent::addField($fieldName, $field);
    }

    /**
     * Indicates whether the exclusion policy is set explicitly.
     *
     * @return bool
     */
    public function hasExclusionPolicy()
    {
        return array_key_exists(self::EXCLUSION_POLICY, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function setExclusionPolicy($exclusionPolicy)
    {
        $this->items[self::EXCLUSION_POLICY] = $exclusionPolicy;
    }

    /**
     * Indicates whether the partial load flag is set explicitly.
     *
     * @return bool
     */
    public function hasPartialLoad()
    {
        return array_key_exists(self::DISABLE_PARTIAL_LOAD, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function enablePartialLoad()
    {
        $this->items[self::DISABLE_PARTIAL_LOAD] = false;
    }

    /**
     * Indicates whether the entity should be collapsed.
     * It means that target entity should be returned as a value, instead of an array with values of entity fields.
     * Usually this property is set by "get_relation_config" processors to get identifier of the related entity.
     *
     * @return bool
     */
    public function isCollapsed()
    {
        return array_key_exists(FieldConfig::COLLAPSE, $this->items)
            ? $this->items[FieldConfig::COLLAPSE]
            : false;
    }

    /**
     * Sets a flag indicates whether the entity should be collapsed.
     * It means that target entity should be returned as a value, instead of an array with values of entity fields.
     * Usually this property is set by "get_relation_config" processors to get identifier of the related entity.
     *
     * @param bool $collapse
     */
    public function setCollapsed($collapse = true)
    {
        if ($collapse) {
            $this->items[FieldConfig::COLLAPSE] = $collapse;
        } else {
            unset($this->items[FieldConfig::COLLAPSE]);
        }
    }

    /**
     * Indicates whether the maximum number of items is set.
     *
     * @return bool
     */
    public function hasMaxResults()
    {
        return array_key_exists(self::MAX_RESULTS, $this->items);
    }

    /**
     * Gets the maximum number of items in the result.
     *
     * @return int|null The requested maximum number of items, NULL or -1 if not limited
     */
    public function getMaxResults()
    {
        return array_key_exists(self::MAX_RESULTS, $this->items)
            ? $this->items[self::MAX_RESULTS]
            : null;
    }

    /**
     * Sets the maximum number of items in the result.
     * Set NULL to use a default limit.
     * Set -1 (it means unlimited), zero or positive value to set own limit.
     *
     * @param int|null $maxResults The maximum number of items, NULL or -1 to set unlimited
     */
    public function setMaxResults($maxResults = null)
    {
        if (null === $maxResults) {
            unset($this->items[self::MAX_RESULTS]);
        } else {
            $maxResults = (int)$maxResults;

            $this->items[self::MAX_RESULTS] = $maxResults >= 0 ? $maxResults : -1;
        }
    }

    /**
     * Sets Doctrine query hints.
     *
     * @param array|null $hints
     */
    public function setHints($hints = null)
    {
        if (!empty($hints)) {
            $this->items[self::HINTS] = $hints;
        } else {
            unset($this->items[self::HINTS]);
        }
    }
}
