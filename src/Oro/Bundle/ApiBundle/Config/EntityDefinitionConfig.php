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
    use Traits\DescriptionTrait;
    use Traits\DocumentationTrait;
    use Traits\AclResourceTrait;
    use Traits\MaxResultsTrait;
    use Traits\PageSizeTrait;
    use Traits\SortingTrait;
    use Traits\InclusionTrait;
    use Traits\FieldsetTrait;
    use Traits\FormTrait;
    use Traits\StatusCodesTrait;

    /** a short, human-readable description of API resource */
    const DESCRIPTION = 'description';

    /** a detailed documentation of API resource */
    const DOCUMENTATION = 'documentation';

    /** the name of ACL resource */
    const ACL_RESOURCE = 'acl_resource';

    /** the default page size */
    const PAGE_SIZE = 'page_size';

    /** a flag indicates whether a sorting is disabled */
    const DISABLE_SORTING = 'disable_sorting';

    /** a flag indicates whether an inclusion of related entities is disabled */
    const DISABLE_INCLUSION = 'disable_inclusion';

    /** a flag indicates whether a requesting of a restricted set of fields is disabled */
    const DISABLE_FIELDSET = 'disable_fieldset';

    /** a handler that should be used to delete the entity */
    const DELETE_HANDLER = 'delete_handler';

    /** the form type that should be used for the entity */
    const FORM_TYPE = 'form_type';

    /** the form options that should be used for the entity */
    const FORM_OPTIONS = 'form_options';

    /** the names of identifier fields of the entity */
    const IDENTIFIER_FIELD_NAMES = 'identifier_field_names';

    /** response status codes */
    const STATUS_CODES = 'status_codes';

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
     * Gets the names of identifier fields of the entity.
     *
     * @return string[]
     */
    public function getIdentifierFieldNames()
    {
        return array_key_exists(self::IDENTIFIER_FIELD_NAMES, $this->items)
            ? $this->items[self::IDENTIFIER_FIELD_NAMES]
            : [];
    }

    /**
     * Sets the names of identifier fields of the entity.
     *
     * @param string[] $fields
     */
    public function setIdentifierFieldNames(array $fields)
    {
        if (empty($fields)) {
            unset($this->items[self::IDENTIFIER_FIELD_NAMES]);
        } else {
            $this->items[self::IDENTIFIER_FIELD_NAMES] = $fields;
        }
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
