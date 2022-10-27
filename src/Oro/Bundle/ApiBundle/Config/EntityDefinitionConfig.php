<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\FieldConfigInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Represents the configuration of an entity.
 *
 * @method EntityDefinitionFieldConfig[] getFields()
 * @method EntityDefinitionFieldConfig|null getField(string $fieldName)
 * @method EntityDefinitionFieldConfig getOrAddField(string $fieldName)
 * @method EntityDefinitionFieldConfig|null findField(string $fieldName, bool $findByPropertyPath = false)
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityDefinitionConfig extends EntityConfig
{
    /**
     * A string that unique identify this instance of entity definition config.
     * This value is set by config providers and is used by a metadata provider
     * to build a metadata cache key. It allows to avoid loading the same metadata
     * several times and as result it improves a performance.
     * @see \Oro\Bundle\ApiBundle\Provider\MetadataProvider
     * @see \Oro\Bundle\ApiBundle\Provider\ConfigProvider
     */
    private ?string $key = null;
    /** @var string[] */
    private array $identifierFieldNames = [];

    /**
     * Gets a string that unique identify this instance of entity definition config.
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * Sets a string that unique identify this instance of entity definition config.
     * Do not set this value in your code.
     * @see \Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig::key
     */
    public function setKey(?string $key): void
    {
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function toArray(): array
    {
        $result = parent::toArray();
        if ($this->identifierFieldNames) {
            $result[ConfigUtil::IDENTIFIER_FIELD_NAMES] = $this->identifierFieldNames;
        }
        if (isset($result[ConfigUtil::DISABLE_INCLUSION])
            && false === $result[ConfigUtil::DISABLE_INCLUSION]
        ) {
            unset($result[ConfigUtil::DISABLE_INCLUSION]);
        }
        if (isset($result[ConfigUtil::DISABLE_FIELDSET])
            && false === $result[ConfigUtil::DISABLE_FIELDSET]
        ) {
            unset($result[ConfigUtil::DISABLE_FIELDSET]);
        }
        if (isset($result[ConfigUtil::DISABLE_META_PROPERTIES])
            && false === $result[ConfigUtil::DISABLE_META_PROPERTIES]
        ) {
            unset($result[ConfigUtil::DISABLE_META_PROPERTIES]);
        }
        if (isset($result[ConfigUtil::DISABLE_PARTIAL_LOAD])
            && false === $result[ConfigUtil::DISABLE_PARTIAL_LOAD]
        ) {
            unset($result[ConfigUtil::DISABLE_PARTIAL_LOAD]);
        }
        if (isset($result[ConfigUtil::DISABLE_SORTING]) && false === $result[ConfigUtil::DISABLE_SORTING]) {
            unset($result[ConfigUtil::DISABLE_SORTING]);
        }
        if (isset($result[ConfigUtil::COLLAPSE]) && false === $result[ConfigUtil::COLLAPSE]) {
            unset($result[ConfigUtil::COLLAPSE]);
        }

        $keys = array_keys($result);
        foreach ($keys as $key) {
            $value = $result[$key];
            if (\is_object($value) && method_exists($value, 'toArray')) {
                $result[$key] = $value->toArray();
            }
        }

        if (isset($result[ConfigUtil::FIELDS])) {
            $fieldNames = array_keys($result[ConfigUtil::FIELDS]);
            foreach ($fieldNames as $fieldName) {
                if (empty($result[ConfigUtil::FIELDS][$fieldName])) {
                    $result[ConfigUtil::FIELDS][$fieldName] = null;
                }
            }
        }

        return $result;
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
    public function findFieldByPath(string|array $path, bool $findByPropertyPath = false): ?EntityDefinitionFieldConfig
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
     * Adds the configuration of a field.
     *
     * @param string                           $fieldName
     * @param EntityDefinitionFieldConfig|null $field
     *
     * @return EntityDefinitionFieldConfig
     */
    public function addField(string $fieldName, FieldConfigInterface $field = null): EntityDefinitionFieldConfig
    {
        if (null === $field) {
            $field = new EntityDefinitionFieldConfig();
        }

        return parent::addField($fieldName, $field);
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
     * Gets the class name of a parent API resource.
     */
    public function getParentResourceClass(): ?string
    {
        return $this->get(ConfigUtil::PARENT_RESOURCE_CLASS);
    }

    /**
     * Sets the class name of a parent API resource.
     */
    public function setParentResourceClass(?string $parentResourceClass): void
    {
        if ($parentResourceClass) {
            $this->items[ConfigUtil::PARENT_RESOURCE_CLASS] = $parentResourceClass;
        } else {
            unset($this->items[ConfigUtil::PARENT_RESOURCE_CLASS]);
        }
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
     * Indicates whether the documentation attribute exists.
     */
    public function hasDocumentation(): bool
    {
        return $this->has(ConfigUtil::DOCUMENTATION);
    }

    /**
     * Gets a detailed documentation of API resource.
     */
    public function getDocumentation(): ?string
    {
        return $this->get(ConfigUtil::DOCUMENTATION);
    }

    /**
     * Sets a detailed documentation of API resource.
     */
    public function setDocumentation(?string $documentation): void
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
    public function getIdentifierFieldNames(): array
    {
        return $this->identifierFieldNames;
    }

    /**
     * Sets the names of identifier fields of the entity.
     *
     * @param string[] $fields
     */
    public function setIdentifierFieldNames(array $fields): void
    {
        $this->identifierFieldNames = $fields;
    }

    /**
     * Checks whether this configuration represent a request for the entity identifier only
     * or values of other fields should be returned as well.
     */
    public function isIdentifierOnlyRequested(): bool
    {
        if (empty($this->identifierFieldNames)
            || \count($this->fields) !== \count($this->identifierFieldNames)
        ) {
            return false;
        }

        $isIdentifierOnly = true;
        foreach ($this->identifierFieldNames as $idFieldName) {
            if (!isset($this->fields[$idFieldName])) {
                $isIdentifierOnly = false;
                break;
            }
        }

        return $isIdentifierOnly;
    }

    /**
     * Indicates whether the entity should be collapsed.
     * It means that target entity should be returned as a value, instead of an array with values of entity fields.
     */
    public function isCollapsed(): bool
    {
        return $this->get(ConfigUtil::COLLAPSE, false);
    }

    /**
     * Sets a flag indicates whether the entity should be collapsed.
     * It means that target entity should be returned as a value, instead of an array with values of entity fields.
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
     * Indicates whether the name of ACL resource is set explicitly.
     */
    public function hasAclResource(): bool
    {
        return $this->has(ConfigUtil::ACL_RESOURCE);
    }

    /**
     * Gets the name of ACL resource that should be used to protect the entity.
     * Return null if access should not be check.
     */
    public function getAclResource(): ?string
    {
        return $this->get(ConfigUtil::ACL_RESOURCE);
    }

    /**
     * Sets the name of ACL resource that should be used to protect the entity.
     * Set null if access should not be check.
     */
    public function setAclResource(?string $aclResource): void
    {
        $this->items[ConfigUtil::ACL_RESOURCE] = $aclResource;
    }

    /**
     * Sets Doctrine query hints.
     * Each hint can be a string or an associative array with "name" and "value" keys.
     */
    public function setHints(?array $hints): void
    {
        if ($hints) {
            $this->items[ConfigUtil::HINTS] = $hints;
        } else {
            unset($this->items[ConfigUtil::HINTS]);
        }
    }

    /**
     * Indicates whether at least one link to documentation file exists.
     */
    public function hasDocumentationResources(): bool
    {
        return $this->has(ConfigUtil::DOCUMENTATION_RESOURCE);
    }

    /**
     * Gets links to files contain the documentation for API resource.
     *
     * @return string[]
     */
    public function getDocumentationResources(): array
    {
        return $this->get(ConfigUtil::DOCUMENTATION_RESOURCE, []);
    }

    /**
     * Sets links to files contain the documentation for API resource.
     *
     * @param string[]|string|null $resource
     */
    public function setDocumentationResources(array|string|null $resource): void
    {
        if ($resource) {
            $this->items[ConfigUtil::DOCUMENTATION_RESOURCE] = (array)$resource;
        } else {
            unset($this->items[ConfigUtil::DOCUMENTATION_RESOURCE]);
        }
    }

    /**
     * Indicates whether a human-readable description of the API resource identifier exists.
     */
    public function hasIdentifierDescription(): bool
    {
        return $this->has(ConfigUtil::IDENTIFIER_DESCRIPTION);
    }

    /**
     * Gets a human-readable description of the API resource identifier.
     */
    public function getIdentifierDescription(): string|Label|null
    {
        return $this->get(ConfigUtil::IDENTIFIER_DESCRIPTION);
    }

    /**
     * Sets a human-readable description of the API resource identifier.
     */
    public function setIdentifierDescription(string|Label|null $description): void
    {
        if ($description) {
            $this->items[ConfigUtil::IDENTIFIER_DESCRIPTION] = $description;
        } else {
            unset($this->items[ConfigUtil::IDENTIFIER_DESCRIPTION]);
        }
    }

    /**
     * Gets the form type.
     */
    public function getFormType(): ?string
    {
        return $this->get(ConfigUtil::FORM_TYPE);
    }

    /**
     * Sets the form type.
     */
    public function setFormType(?string $formType): void
    {
        if ($formType) {
            $this->items[ConfigUtil::FORM_TYPE] = $formType;
        } else {
            unset($this->items[ConfigUtil::FORM_TYPE]);
        }
    }

    /**
     * Gets the form options.
     */
    public function getFormOptions(): ?array
    {
        return $this->get(ConfigUtil::FORM_OPTIONS);
    }

    /**
     * Sets the form options.
     */
    public function setFormOptions(?array $formOptions): void
    {
        if ($formOptions) {
            $this->items[ConfigUtil::FORM_OPTIONS] = $formOptions;
        } else {
            unset($this->items[ConfigUtil::FORM_OPTIONS]);
        }
    }

    /**
     * Sets a form option. If an option is already exist its value will be replaced with new value.
     */
    public function setFormOption(string $name, mixed $value): void
    {
        $formOptions = $this->getFormOptions();
        $formOptions[$name] = $value;
        $this->setFormOptions($formOptions);
    }

    /**
     * Gets existing validation constraints from the form options.
     *
     * @return array|null [Constraint object or [constraint name or class => constraint options, ...], ...]
     */
    public function getFormConstraints(): ?array
    {
        return FormConstraintUtil::getFormConstraints($this->getFormOptions());
    }

    /**
     * Adds a validation constraint to the form options.
     */
    public function addFormConstraint(Constraint $constraint): void
    {
        $this->setFormOptions(FormConstraintUtil::addFormConstraint($this->getFormOptions(), $constraint));
    }

    /**
     * Removes a validation constraint from the form options by its class.
     */
    public function removeFormConstraint(string $constraintClass): void
    {
        $this->setFormOptions(FormConstraintUtil::removeFormConstraint($this->getFormOptions(), $constraintClass));
    }

    /**
     * Gets the form event subscribers.
     *
     * @return string[]|null Each element in the array is the name of a service implements EventSubscriberInterface
     */
    public function getFormEventSubscribers(): ?array
    {
        return $this->get(ConfigUtil::FORM_EVENT_SUBSCRIBER);
    }

    /**
     * Sets the form event subscribers.
     *
     * @param string[]|null $eventSubscribers Each element in the array should be
     *                                        the name of a service implements EventSubscriberInterface
     */
    public function setFormEventSubscribers(?array $eventSubscribers): void
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
    public function addFormEventSubscriber(string $eventSubscriber): void
    {
        $eventSubscribers = $this->getFormEventSubscribers();
        $eventSubscribers[] = $eventSubscriber;
        $this->setFormEventSubscribers($eventSubscribers);
    }

    /**
     * Indicates whether the "disable_fieldset" option is set explicitly.
     */
    public function hasDisableFieldset(): bool
    {
        return $this->has(ConfigUtil::DISABLE_FIELDSET);
    }

    /**
     * Indicates whether indicates whether a requesting of a restricted set of fields is enabled.
     */
    public function isFieldsetEnabled(): bool
    {
        return !$this->get(ConfigUtil::DISABLE_FIELDSET, false);
    }

    /**
     * Enables a requesting of a restricted set of fields.
     */
    public function enableFieldset(): void
    {
        $this->items[ConfigUtil::DISABLE_FIELDSET] = false;
    }

    /**
     * Disables a requesting of a restricted set of fields.
     */
    public function disableFieldset(): void
    {
        $this->items[ConfigUtil::DISABLE_FIELDSET] = true;
    }

    /**
     * Indicates whether the "disable_inclusion" option is set explicitly.
     */
    public function hasDisableInclusion(): bool
    {
        return $this->has(ConfigUtil::DISABLE_INCLUSION);
    }

    /**
     * Indicates whether an inclusion of related entities is enabled.
     */
    public function isInclusionEnabled(): bool
    {
        return !$this->get(ConfigUtil::DISABLE_INCLUSION, false);
    }

    /**
     * Enables an inclusion of related entities.
     */
    public function enableInclusion(): void
    {
        $this->items[ConfigUtil::DISABLE_INCLUSION] = false;
    }

    /**
     * Disables an inclusion of related entities.
     */
    public function disableInclusion(): void
    {
        $this->items[ConfigUtil::DISABLE_INCLUSION] = true;
    }

    /**
     * Indicates whether the "disable_meta_properties" option is set explicitly.
     */
    public function hasDisableMetaProperties(): bool
    {
        return $this->has(ConfigUtil::DISABLE_META_PROPERTIES);
    }

    /**
     * Indicates whether a requesting of additional meta properties is enabled.
     */
    public function isMetaPropertiesEnabled(): bool
    {
        return !$this->get(ConfigUtil::DISABLE_META_PROPERTIES, false);
    }

    /**
     * Enables a requesting of additional meta properties.
     */
    public function enableMetaProperties(): void
    {
        $this->items[ConfigUtil::DISABLE_META_PROPERTIES] = false;
    }

    /**
     * Disables a requesting of additional meta properties.
     */
    public function disableMetaProperties(): void
    {
        $this->items[ConfigUtil::DISABLE_META_PROPERTIES] = true;
    }

    /**
     * Indicates whether the "disable_sorting" option is set explicitly.
     */
    public function hasDisableSorting(): bool
    {
        return $this->has(ConfigUtil::DISABLE_SORTING);
    }

    /**
     * Indicates whether a sorting is enabled.
     */
    public function isSortingEnabled(): bool
    {
        return !$this->get(ConfigUtil::DISABLE_SORTING, false);
    }

    /**
     * Enables a sorting.
     */
    public function enableSorting(): void
    {
        $this->items[ConfigUtil::DISABLE_SORTING] = false;
    }

    /**
     * Disables a sorting.
     */
    public function disableSorting(): void
    {
        $this->items[ConfigUtil::DISABLE_SORTING] = true;
    }

    /**
     * Indicates whether the default page size is set.
     */
    public function hasPageSize(): bool
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
    public function getPageSize(): ?int
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
    public function setPageSize(?int $pageSize): void
    {
        if (null === $pageSize) {
            unset($this->items[ConfigUtil::PAGE_SIZE]);
        } else {
            $this->items[ConfigUtil::PAGE_SIZE] = $pageSize >= 0 ? $pageSize : -1;
        }
    }

    /**
     * Indicates whether the ordering of items is set.
     */
    public function hasOrderBy(): bool
    {
        return $this->has(ConfigUtil::ORDER_BY);
    }

    /**
     * Indicates whether the maximum number of items is set.
     */
    public function hasMaxResults(): bool
    {
        return $this->has(ConfigUtil::MAX_RESULTS);
    }

    /**
     * Gets the maximum number of items in the result.
     *
     * @return int|null The requested maximum number of items, NULL or -1 if not limited
     */
    public function getMaxResults(): ?int
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
    public function setMaxResults(?int $maxResults): void
    {
        if (null === $maxResults) {
            unset($this->items[ConfigUtil::MAX_RESULTS]);
        } else {
            $this->items[ConfigUtil::MAX_RESULTS] = $maxResults >= 0 ? $maxResults : -1;
        }
    }

    /**
     * Gets response status codes.
     */
    public function getStatusCodes(): ?StatusCodesConfig
    {
        return $this->get(ConfigUtil::STATUS_CODES);
    }

    /**
     * Sets response status codes.
     */
    public function setStatusCodes(?StatusCodesConfig $statusCodes): void
    {
        $this->set(ConfigUtil::STATUS_CODES, $statusCodes);
    }
}
