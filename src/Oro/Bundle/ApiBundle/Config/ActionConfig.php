<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Validator\Constraint;

/**
 * Represents the configuration of Data API resource action.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ActionConfig implements ConfigBagInterface
{
    /** @var bool|null */
    protected $exclude;

    /** @var array */
    protected $items = [];

    /** @var ActionFieldConfig[] */
    protected $fields = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function toArray()
    {
        $result = ConfigUtil::convertItemsToArray($this->items);
        if (null !== $this->exclude) {
            $result[ConfigUtil::EXCLUDE] = $this->exclude;
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

        $fields = ConfigUtil::convertObjectsToArray($this->fields, true);
        if ($fields) {
            $result[ConfigUtil::FIELDS] = $fields;
        }

        return $result;
    }

    /**
     * Indicates whether the action does not have a configuration.
     *
     * @return bool
     */
    public function isEmpty()
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
     *
     * @return bool
     */
    public function hasFields()
    {
        return !empty($this->fields);
    }

    /**
     * Gets the configuration for all actions.
     *
     * @return ActionFieldConfig[] [field name => config, ...]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Indicates whether the configuration of the action exists.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasField($fieldName)
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * Gets the configuration of the action.
     *
     * @param string $fieldName
     *
     * @return ActionFieldConfig|null
     */
    public function getField($fieldName)
    {
        if (!isset($this->fields[$fieldName])) {
            return null;
        }

        return $this->fields[$fieldName];
    }

    /**
     * Gets the configuration of existing action or adds new action.
     *
     * @param string $fieldName
     *
     * @return ActionFieldConfig
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
     * Adds the configuration of the action.
     *
     * @param string                 $fieldName
     * @param ActionFieldConfig|null $field
     *
     * @return ActionFieldConfig
     */
    public function addField($fieldName, $field = null)
    {
        if (null === $field) {
            $field = new ActionFieldConfig();
        }

        $this->fields[$fieldName] = $field;

        return $field;
    }

    /**
     * Removes the configuration of the action.
     *
     * @param string $fieldName
     */
    public function removeField($fieldName)
    {
        unset($this->fields[$fieldName]);
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return \array_key_exists($key, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $defaultValue = null)
    {
        if (!\array_key_exists($key, $this->items)) {
            return $defaultValue;
        }

        return $this->items[$key];
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
    public function remove($key)
    {
        unset($this->items[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return \array_keys($this->items);
    }

    /**
     * Indicates whether the exclusion flag is set explicitly.
     *
     * @return bool
     */
    public function hasExcluded()
    {
        return null !== $this->exclude;
    }

    /**
     * Indicates whether the exclusion flag.
     *
     * @return bool
     */
    public function isExcluded()
    {
        if (null === $this->exclude) {
            return false;
        }

        return $this->exclude;
    }

    /**
     * Sets the exclusion flag.
     *
     * @param bool|null $exclude The exclude flag or NULL to remove this option
     */
    public function setExcluded($exclude = true)
    {
        $this->exclude = $exclude;
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
     * Gets the default ordering of the result.
     * The direction can be "ASC" or "DESC".
     * The Doctrine\Common\Collections\Criteria::ASC and Doctrine\Common\Collections\Criteria::DESC constants
     * can be used.
     *
     * @return array [field name => direction, ...]
     */
    public function getOrderBy()
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
    public function setOrderBy(array $orderBy = [])
    {
        if ($orderBy) {
            $this->items[ConfigUtil::ORDER_BY] = $orderBy;
        } else {
            unset($this->items[ConfigUtil::ORDER_BY]);
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
