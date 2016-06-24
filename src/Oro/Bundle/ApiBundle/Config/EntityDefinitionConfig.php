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
    use Traits\FindFieldTrait;
    use Traits\LabelTrait;
    use Traits\PluralLabelTrait;
    use Traits\DescriptionTrait;
    use Traits\FormTrait;
    use Traits\AclResourceTrait;
    use Traits\MaxResultsTrait;
    use Traits\StatusCodesTrait;

    /** a human-readable representation of the entity */
    const LABEL = 'label';

    /** a human-readable representation in plural of the entity */
    const PLURAL_LABEL = 'plural_label';

    /** a human-readable description of the entity */
    const DESCRIPTION = 'description';

    /** the default page size */
    const PAGE_SIZE = 'page_size';

    /** a flag indicates whether a sorting is disabled */
    const DISABLE_SORTING = 'disable_sorting';

    /** the name of ACL resource */
    const ACL_RESOURCE = 'acl_resource';

    /** a handler that should be used to delete the entity */
    const DELETE_HANDLER = 'delete_handler';

    /** response status codes */
    const STATUS_CODES = 'status_codes';

    /** the form type that should be used for the entity */
    const FORM_TYPE = 'form_type';

    /** the form options that should be used for the entity */
    const FORM_OPTIONS = 'form_options';

    /**
     * A string that unique identify this instance of entity definition config.
     * This value is set by config providers and is used by a metadata provider
     * to build a metadata cache key. It allows to avoid loading the same metadata
     * several times and as result it improves a performance.
     * @see Oro\Bundle\ApiBundle\Provider\MetadataProvider
     * @see Oro\Bundle\ApiBundle\Provider\ConfigProvider
     * @see Oro\Bundle\ApiBundle\Provider\RelationConfigProvider
     *
     * @var string|null
     */
    protected $key;

    /**
     * Gets a string that unique identify this instance of entity definition config.
     *
     * @return string|null
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Sets a string that unique identify this instance of entity definition config.
     * Do not set this value in your code.
     * @see Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig::id
     *
     * @param string|null $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

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
        return !empty($this->fields);
    }

    /**
     * Finds the configuration of the field by its name or property path.
     * If $findByPropertyPath equals to TRUE do the find using a given field name as a property path.
     *
     * @param string $fieldName
     * @param bool   $findByPropertyPath
     *
     * @return EntityDefinitionFieldConfig|null
     */
    public function findField($fieldName, $findByPropertyPath = false)
    {
        return $this->doFindField($fieldName, $findByPropertyPath);
    }

    /**
     * Finds the name of the field by its property path.
     *
     * @param string $propertyPath
     *
     * @return string|null
     */
    public function findFieldNameByPropertyPath($propertyPath)
    {
        return $this->doFindFieldNameByPropertyPath($propertyPath);
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
     * Indicates whether the default page size is set.
     *
     * @return bool
     */
    public function hasPageSize()
    {
        return array_key_exists(EntityDefinitionConfig::PAGE_SIZE, $this->items);
    }

    /**
     * Gets the default page size.
     *
     * @return int|null A positive number
     *                  NULL if the default page size should be set be a processor
     *                  -1 if the pagination should be disabled
     */
    public function getPageSize()
    {
        return array_key_exists(self::PAGE_SIZE, $this->items)
            ? $this->items[self::PAGE_SIZE]
            : null;
    }

    /**
     * Sets the default page size.
     * Set NULL if the default page size should be set be a processor.
     * Set -1 if the pagination should be disabled.
     * Set a positive number to set own page size that should be used as a default one.
     *
     * @param int|null $pageSize A positive number, NULL or -1
     */
    public function setPageSize($pageSize = null)
    {
        if (null === $pageSize) {
            unset($this->items[self::PAGE_SIZE]);
        } else {
            $pageSize = (int)$pageSize;

            $this->items[self::PAGE_SIZE] = $pageSize >= 0 ? $pageSize : -1;
        }
    }

    /**
     * Indicates whether a sorting is enabled.
     *
     * @return bool
     */
    public function isSortingEnabled()
    {
        return array_key_exists(self::DISABLE_SORTING, $this->items)
            ? !$this->items[self::DISABLE_SORTING]
            : true;
    }

    /**
     * Enables a sorting.
     */
    public function enableSorting()
    {
        unset($this->items[self::DISABLE_SORTING]);
    }

    /**
     * Disables a sorting.
     */
    public function disableSorting()
    {
        $this->items[self::DISABLE_SORTING] = true;
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

    /**
     * Gets a handler that should be used to delete the entity.
     *
     * @return string|null The service id
     */
    public function getDeleteHandler()
    {
        return array_key_exists(self::DELETE_HANDLER, $this->items)
            ? $this->items[self::DELETE_HANDLER]
            : null;
    }

    /**
     * Sets a handler that should be used to delete the entity.
     *
     * @param string|null $handler The service id
     */
    public function setDeleteHandler($handler = null)
    {
        if (null !== $handler) {
            $this->items[self::DELETE_HANDLER] = $handler;
        } else {
            unset($this->items[self::DELETE_HANDLER]);
        }
    }
}
