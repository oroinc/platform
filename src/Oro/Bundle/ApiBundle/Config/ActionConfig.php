<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Validator\Constraint;

/**
 * Represents the configuration of API resource action.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ActionConfig
{
    private ?bool $exclude = null;
    private array $items = [];
    /** @var ActionFieldConfig[] */
    private array $fields = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function toArray(): array
    {
        $result = ConfigUtil::convertItemsToArray($this->items);
        if (null !== $this->exclude) {
            $result[ConfigUtil::EXCLUDE] = $this->exclude;
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

        $fields = ConfigUtil::convertObjectsToArray($this->fields, true);
        if ($fields) {
            $result[ConfigUtil::FIELDS] = $fields;
        }

        return $result;
    }

    /**
     * Indicates whether the action does not have a configuration.
     */
    public function isEmpty(): bool
    {
        return
            null === $this->exclude
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
     * Indicates whether the configuration of at least one action exists.
     */
    public function hasFields(): bool
    {
        return !empty($this->fields);
    }

    /**
     * Gets the configuration for all actions.
     *
     * @return ActionFieldConfig[] [field name => config, ...]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Indicates whether the configuration of the action exists.
     */
    public function hasField(string $fieldName): bool
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * Gets the configuration of the action.
     */
    public function getField(string $fieldName): ?ActionFieldConfig
    {
        return $this->fields[$fieldName] ?? null;
    }

    /**
     * Gets the configuration of existing action or adds new action.
     */
    public function getOrAddField(string $fieldName): ActionFieldConfig
    {
        $field = $this->getField($fieldName);
        if (null === $field) {
            $field = $this->addField($fieldName);
        }

        return $field;
    }

    /**
     * Adds the configuration of the action.
     */
    public function addField(string $fieldName, ActionFieldConfig $field = null): ActionFieldConfig
    {
        if (null === $field) {
            $field = new ActionFieldConfig();
        }

        $this->fields[$fieldName] = $field;

        return $field;
    }

    /**
     * Removes the configuration of the action.
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
     * Indicates whether the name of ACL resource is set explicitly.
     */
    public function hasAclResource(): bool
    {
        return $this->has(ConfigUtil::ACL_RESOURCE);
    }

    /**
     * Gets the name of ACL resource that should be used to protect the entity.
     */
    public function getAclResource(): ?string
    {
        return $this->get(ConfigUtil::ACL_RESOURCE);
    }

    /**
     * Sets the name of ACL resource that should be used to protect the entity.
     */
    public function setAclResource(?string $aclResource): void
    {
        $this->items[ConfigUtil::ACL_RESOURCE] = $aclResource;
    }

    /**
     * Gets the default ordering of the result.
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
     * Sets the default ordering of the result.
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
