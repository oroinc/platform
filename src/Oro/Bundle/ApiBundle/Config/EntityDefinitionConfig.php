<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\EntitySerializer\EntityConfig;
use Symfony\Component\Validator\Constraint;

/**
 * Represents the configuration of an entity.
 *
 * @method EntityDefinitionFieldConfig[] getFields()
 * @method EntityDefinitionFieldConfig|null getField($fieldName)
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityDefinitionConfig extends EntityConfig implements EntityConfigInterface
{
    /**
     * A string that unique identify this instance of entity definition config.
     * This value is set by config providers and is used by a metadata provider
     * to build a metadata cache key. It allows to avoid loading the same metadata
     * several times and as result it improves a performance.
     * @see \Oro\Bundle\ApiBundle\Provider\MetadataProvider
     * @see \Oro\Bundle\ApiBundle\Provider\ConfigProvider
     * @see \Oro\Bundle\ApiBundle\Provider\RelationConfigProvider
     *
     * @var string|null
     */
    private $key;

    /** @var string[] */
    private $identifierFieldNames = [];

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
     * @see \Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig::key
     *
     * @param string|null $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function toArray()
    {
        $result = parent::toArray();
        if ($this->identifierFieldNames) {
            $result[ConfigUtil::IDENTIFIER_FIELD_NAMES] = $this->identifierFieldNames;
        }
        if (isset($result[ConfigUtil::DISABLE_META_PROPERTIES])
            && false === $result[ConfigUtil::DISABLE_META_PROPERTIES]
        ) {
            unset($result[ConfigUtil::DISABLE_META_PROPERTIES]);
        }
        if (isset($result[ConfigUtil::DISABLE_INCLUSION]) && false === $result[ConfigUtil::DISABLE_INCLUSION]) {
            unset($result[ConfigUtil::DISABLE_INCLUSION]);
        }
        if (isset($result[ConfigUtil::DISABLE_FIELDSET]) && false === $result[ConfigUtil::DISABLE_FIELDSET]) {
            unset($result[ConfigUtil::DISABLE_FIELDSET]);
        }
        if (isset($result[ConfigUtil::DISABLE_SORTING]) && false === $result[ConfigUtil::DISABLE_SORTING]) {
            unset($result[ConfigUtil::DISABLE_SORTING]);
        }
        if (isset($result[ConfigUtil::COLLAPSE]) && false === $result[ConfigUtil::COLLAPSE]) {
            unset($result[ConfigUtil::COLLAPSE]);
        }

        $keys = \array_keys($result);
        foreach ($keys as $key) {
            $value = $result[$key];
            if (\is_object($value) && \method_exists($value, 'toArray')) {
                $result[$key] = $value->toArray();
            }
        }

        if (isset($result[ConfigUtil::FIELDS])) {
            $fieldNames = \array_keys($result[ConfigUtil::FIELDS]);
            foreach ($fieldNames as $fieldName) {
                if (empty($result[ConfigUtil::FIELDS][$fieldName])) {
                    $result[ConfigUtil::FIELDS][$fieldName] = null;
                }
            }
        }

        return $result;
    }

    /**
     * Indicates whether the configuration of at least one field exists.
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
        return FindFieldUtil::doFindField($this->fields, $fieldName, $findByPropertyPath);
    }

    /**
     * Finds the name of the field by its property path.
     * This method can be useful when a field was renamed and you need to find
     * the name of the result field by the name defined in an entity.
     *
     * @param string $propertyPath
     *
     * @return string|null
     */
    public function findFieldNameByPropertyPath($propertyPath)
    {
        return FindFieldUtil::doFindFieldNameByPropertyPath($this->fields, $propertyPath);
    }

    /**
     * Finds the configuration of a child field by its name or property path.
     * If $findByPropertyPath equals to TRUE do the find using a given field name as a property path.
     *
     * @param string|string[] $path
     * @param bool            $findByPropertyPath
     *
     * @return EntityDefinitionFieldConfig|null
     */
    public function findFieldByPath($path, $findByPropertyPath = false)
    {
        $targetConfig = $this;
        if (!\is_array($path)) {
            $path = ConfigUtil::explodePropertyPath($path);
        }
        $pathCount = \count($path);
        for ($i = 0; $i < $pathCount - 1; $i++) {
            $fieldConfig = $targetConfig->findField($path[$i], $findByPropertyPath);
            if (null === $fieldConfig) {
                return null;
            }
            $targetConfig = $fieldConfig->getTargetEntity();
            if (null === $targetConfig) {
                return null;
            }
        }

        return $targetConfig->findField($path[$pathCount - 1], $findByPropertyPath);
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
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        if (null !== $value) {
            $this->items[$key] = $value;
        } else {
            unset($this->items[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return \array_keys($this->items);
    }

    /**
     * Indicates whether the exclusion policy is set explicitly.
     *
     * @return bool
     */
    public function hasExclusionPolicy()
    {
        return null !== $this->exclusionPolicy;
    }

    /**
     * Gets the class name of a parent API resource.
     *
     * @return string|null
     */
    public function getParentResourceClass()
    {
        return $this->get(ConfigUtil::PARENT_RESOURCE_CLASS);
    }

    /**
     * Sets the class name of a parent API resource.
     *
     * @param string|null $parentResourceClass
     */
    public function setParentResourceClass($parentResourceClass)
    {
        if ($parentResourceClass) {
            $this->items[ConfigUtil::PARENT_RESOURCE_CLASS] = $parentResourceClass;
        } else {
            unset($this->items[ConfigUtil::PARENT_RESOURCE_CLASS]);
        }
    }

    /**
     * Indicates whether the description attribute exists.
     *
     * @return bool
     */
    public function hasDescription()
    {
        return $this->has(ConfigUtil::DESCRIPTION);
    }

    /**
     * Gets the value of the description attribute.
     *
     * @return string|Label|null
     */
    public function getDescription()
    {
        return $this->get(ConfigUtil::DESCRIPTION);
    }

    /**
     * Sets the value of the description attribute.
     *
     * @param string|Label|null $description
     */
    public function setDescription($description)
    {
        if ($description) {
            $this->items[ConfigUtil::DESCRIPTION] = $description;
        } else {
            unset($this->items[ConfigUtil::DESCRIPTION]);
        }
    }

    /**
     * Indicates whether the documentation attribute exists.
     *
     * @return bool
     */
    public function hasDocumentation()
    {
        return $this->has(ConfigUtil::DOCUMENTATION);
    }

    /**
     * Gets a detailed documentation of API resource.
     *
     * @return string|null
     */
    public function getDocumentation()
    {
        return $this->get(ConfigUtil::DOCUMENTATION);
    }

    /**
     * Sets a detailed documentation of API resource.
     *
     * @param string|null $documentation
     */
    public function setDocumentation($documentation)
    {
        if ($documentation) {
            $this->items[ConfigUtil::DOCUMENTATION] = $documentation;
        } else {
            unset($this->items[ConfigUtil::DOCUMENTATION]);
        }
    }

    /**
     * Gets the names of identifier fields of the entity.
     *
     * @return string[]
     */
    public function getIdentifierFieldNames()
    {
        return $this->identifierFieldNames;
    }

    /**
     * Sets the names of identifier fields of the entity.
     *
     * @param string[] $fields
     */
    public function setIdentifierFieldNames(array $fields)
    {
        $this->identifierFieldNames = $fields;
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
        return $this->get(ConfigUtil::COLLAPSE, false);
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
            $this->items[ConfigUtil::COLLAPSE] = $collapse;
        } else {
            unset($this->items[ConfigUtil::COLLAPSE]);
        }
    }

    /**
     * Indicates whether the name of ACL resource is set explicitly.
     *
     * @return bool
     */
    public function hasAclResource()
    {
        return $this->has(ConfigUtil::ACL_RESOURCE);
    }

    /**
     * Gets the name of ACL resource that should be used to protect the entity.
     *
     * @return string|null
     */
    public function getAclResource()
    {
        return $this->get(ConfigUtil::ACL_RESOURCE);
    }

    /**
     * Sets the name of ACL resource that should be used to protect the entity.
     *
     * @param string|null $aclResource
     */
    public function setAclResource($aclResource = null)
    {
        $this->items[ConfigUtil::ACL_RESOURCE] = $aclResource;
    }

    /**
     * Sets Doctrine query hints.
     *
     * @param array|null $hints
     */
    public function setHints($hints = null)
    {
        if ($hints) {
            $this->items[ConfigUtil::HINTS] = $hints;
        } else {
            unset($this->items[ConfigUtil::HINTS]);
        }
    }

    /**
     * Gets a handler that should be used to delete the entity.
     *
     * @return string|null The service id
     */
    public function getDeleteHandler()
    {
        return $this->get(ConfigUtil::DELETE_HANDLER);
    }

    /**
     * Sets a handler that should be used to delete the entity.
     *
     * @param string|null $handler The service id
     */
    public function setDeleteHandler($handler = null)
    {
        $this->set(ConfigUtil::DELETE_HANDLER, $handler);
    }

    /**
     * Indicates whether at least one link to documentation file exists.
     *
     * @return bool
     */
    public function hasDocumentationResources()
    {
        return $this->has(ConfigUtil::DOCUMENTATION_RESOURCE);
    }

    /**
     * Gets links to files contain the documentation for API resource.
     *
     * @return string[]
     */
    public function getDocumentationResources()
    {
        return $this->get(ConfigUtil::DOCUMENTATION_RESOURCE, []);
    }

    /**
     * Sets links to files contain the documentation for API resource.
     *
     * @param string[]|string|null $resource
     */
    public function setDocumentationResources($resource)
    {
        if ($resource) {
            $this->items[ConfigUtil::DOCUMENTATION_RESOURCE] = (array)$resource;
        } else {
            unset($this->items[ConfigUtil::DOCUMENTATION_RESOURCE]);
        }
    }

    /**
     * Indicates whether a human-readable description of the API resource identifier exists.
     *
     * @return bool
     */
    public function hasIdentifierDescription()
    {
        return $this->has(ConfigUtil::IDENTIFIER_DESCRIPTION);
    }

    /**
     * Gets a human-readable description of the API resource identifier.
     *
     * @return string|Label|null
     */
    public function getIdentifierDescription()
    {
        return $this->get(ConfigUtil::IDENTIFIER_DESCRIPTION);
    }

    /**
     * Sets a human-readable description of the API resource identifier.
     *
     * @param string|Label|null $description
     */
    public function setIdentifierDescription($description)
    {
        if ($description) {
            $this->items[ConfigUtil::IDENTIFIER_DESCRIPTION] = $description;
        } else {
            unset($this->items[ConfigUtil::IDENTIFIER_DESCRIPTION]);
        }
    }

    /**
     * Gets the form type.
     *
     * @return string|null
     */
    public function getFormType()
    {
        return $this->get(ConfigUtil::FORM_TYPE);
    }

    /**
     * Sets the form type.
     *
     * @param string|null $formType
     */
    public function setFormType($formType)
    {
        if ($formType) {
            $this->items[ConfigUtil::FORM_TYPE] = $formType;
        } else {
            unset($this->items[ConfigUtil::FORM_TYPE]);
        }
    }

    /**
     * Gets the form options.
     *
     * @return array|null
     */
    public function getFormOptions()
    {
        return $this->get(ConfigUtil::FORM_OPTIONS);
    }

    /**
     * Sets the form options.
     *
     * @param array|null $formOptions
     */
    public function setFormOptions($formOptions)
    {
        if ($formOptions) {
            $this->items[ConfigUtil::FORM_OPTIONS] = $formOptions;
        } else {
            unset($this->items[ConfigUtil::FORM_OPTIONS]);
        }
    }

    /**
     * Sets a form option. If an option is already exist its value will be replaced with new value.
     *
     * @param string $name  The name of an option
     * @param mixed  $value The value of an option
     */
    public function setFormOption($name, $value)
    {
        $formOptions = $this->getFormOptions();
        $formOptions[$name] = $value;
        $this->setFormOptions($formOptions);
    }

    /**
     * Gets existing validation constraints from the form options.
     *
     * @return Constraint[]|null
     */
    public function getFormConstraints()
    {
        $formOptions = $this->getFormOptions();
        if (empty($formOptions) || !\array_key_exists('constraints', $formOptions)) {
            return null;
        }

        return $formOptions['constraints'];
    }

    /**
     * Adds a validation constraint to the form options.
     *
     * @param Constraint $constraint
     */
    public function addFormConstraint(Constraint $constraint)
    {
        $formOptions = $this->getFormOptions();
        $formOptions['constraints'][] = $constraint;
        $this->setFormOptions($formOptions);
    }

    /**
     * Gets the form event subscribers.
     *
     * @return string[]|null Each element in the array is the name of a service implements EventSubscriberInterface
     */
    public function getFormEventSubscribers()
    {
        return $this->get(ConfigUtil::FORM_EVENT_SUBSCRIBER);
    }

    /**
     * Sets the form event subscribers.
     *
     * @param string[]|null $eventSubscribers Each element in the array should be
     *                                        the name of a service implements EventSubscriberInterface
     */
    public function setFormEventSubscribers(array $eventSubscribers = null)
    {
        if ($eventSubscribers) {
            $this->items[ConfigUtil::FORM_EVENT_SUBSCRIBER] = $eventSubscribers;
        } else {
            unset($this->items[ConfigUtil::FORM_EVENT_SUBSCRIBER]);
        }
    }

    /**
     * Adds the form event subscriber.
     *
     * @param string $eventSubscriber The name of a service implements EventSubscriberInterface
     */
    public function addFormEventSubscriber($eventSubscriber)
    {
        $eventSubscribers = $this->getFormEventSubscribers();
        $eventSubscribers[] = $eventSubscriber;
        $this->setFormEventSubscribers($eventSubscribers);
    }

    /**
     * Indicates whether the "disable_meta_properties" option is set explicitly.
     *
     * @return bool
     */
    public function hasDisableMetaProperties()
    {
        return $this->has(ConfigUtil::DISABLE_META_PROPERTIES);
    }

    /**
     * Indicates whether a requesting of additional meta properties is enabled.
     *
     * @return bool
     */
    public function isMetaPropertiesEnabled()
    {
        return !$this->get(ConfigUtil::DISABLE_META_PROPERTIES, false);
    }

    /**
     * Enables a requesting of additional meta properties.
     */
    public function enableMetaProperties()
    {
        $this->items[ConfigUtil::DISABLE_META_PROPERTIES] = false;
    }

    /**
     * Disables a requesting of additional meta properties.
     */
    public function disableMetaProperties()
    {
        $this->items[ConfigUtil::DISABLE_META_PROPERTIES] = true;
    }

    /**
     * Indicates whether the "disable_fieldset" option is set explicitly.
     *
     * @return bool
     */
    public function hasDisableFieldset()
    {
        return $this->has(ConfigUtil::DISABLE_FIELDSET);
    }

    /**
     * Indicates whether indicates whether a requesting of a restricted set of fields is enabled.
     *
     * @return bool
     */
    public function isFieldsetEnabled()
    {
        return !$this->get(ConfigUtil::DISABLE_FIELDSET, false);
    }

    /**
     * Enables a requesting of a restricted set of fields.
     */
    public function enableFieldset()
    {
        $this->items[ConfigUtil::DISABLE_FIELDSET] = false;
    }

    /**
     * Disables a requesting of a restricted set of fields.
     */
    public function disableFieldset()
    {
        $this->items[ConfigUtil::DISABLE_FIELDSET] = true;
    }

    /**
     * Indicates whether the "disable_inclusion" option is set explicitly.
     *
     * @return bool
     */
    public function hasDisableInclusion()
    {
        return $this->has(ConfigUtil::DISABLE_INCLUSION);
    }

    /**
     * Indicates whether an inclusion of related entities is enabled.
     *
     * @return bool
     */
    public function isInclusionEnabled()
    {
        return !$this->get(ConfigUtil::DISABLE_INCLUSION, false);
    }

    /**
     * Enables an inclusion of related entities.
     */
    public function enableInclusion()
    {
        $this->items[ConfigUtil::DISABLE_INCLUSION] = false;
    }

    /**
     * Disables an inclusion of related entities.
     */
    public function disableInclusion()
    {
        $this->items[ConfigUtil::DISABLE_INCLUSION] = true;
    }

    /**
     * Indicates whether the "disable_sorting" option is set explicitly.
     *
     * @return bool
     */
    public function hasDisableSorting()
    {
        return $this->has(ConfigUtil::DISABLE_SORTING);
    }

    /**
     * Indicates whether a sorting is enabled.
     *
     * @return bool
     */
    public function isSortingEnabled()
    {
        return !$this->get(ConfigUtil::DISABLE_SORTING, false);
    }

    /**
     * Enables a sorting.
     */
    public function enableSorting()
    {
        $this->items[ConfigUtil::DISABLE_SORTING] = false;
    }

    /**
     * Disables a sorting.
     */
    public function disableSorting()
    {
        $this->items[ConfigUtil::DISABLE_SORTING] = true;
    }

    /**
     * Indicates whether the default page size is set.
     *
     * @return bool
     */
    public function hasPageSize()
    {
        return $this->has(ConfigUtil::PAGE_SIZE);
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
        return $this->get(ConfigUtil::PAGE_SIZE);
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
            unset($this->items[ConfigUtil::PAGE_SIZE]);
        } else {
            $pageSize = (int)$pageSize;
            $this->items[ConfigUtil::PAGE_SIZE] = $pageSize >= 0 ? $pageSize : -1;
        }
    }

    /**
     * Indicates whether the maximum number of items is set.
     *
     * @return bool
     */
    public function hasMaxResults()
    {
        return $this->has(ConfigUtil::MAX_RESULTS);
    }

    /**
     * Gets the maximum number of items in the result.
     *
     * @return int|null The requested maximum number of items, NULL or -1 if not limited
     */
    public function getMaxResults()
    {
        return $this->get(ConfigUtil::MAX_RESULTS);
    }

    /**
     * Sets the maximum number of items in the result.
     * Set NULL to use a default limit.
     * Set -1 (it means unlimited), zero or positive number to set own limit.
     *
     * @param int|null $maxResults The maximum number of items, NULL or -1 to set unlimited
     */
    public function setMaxResults($maxResults = null)
    {
        if (null === $maxResults) {
            unset($this->items[ConfigUtil::MAX_RESULTS]);
        } else {
            $maxResults = (int)$maxResults;
            $this->items[ConfigUtil::MAX_RESULTS] = $maxResults >= 0 ? $maxResults : -1;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setHasMore($hasMore)
    {
        parent::setHasMore($hasMore);
        $fields = $this->getFields();
        foreach ($fields as $field) {
            $targetConfig = $field->getTargetEntity();
            if (null !== $targetConfig) {
                $targetConfig->setHasMore($hasMore);
            }
        }
    }

    /**
     * Gets response status codes.
     *
     * @return StatusCodesConfig|null
     */
    public function getStatusCodes()
    {
        return $this->get(ConfigUtil::STATUS_CODES);
    }

    /**
     * Sets response status codes.
     *
     * @param StatusCodesConfig|null $statusCodes
     */
    public function setStatusCodes(StatusCodesConfig $statusCodes = null)
    {
        $this->set(ConfigUtil::STATUS_CODES, $statusCodes);
    }
}
