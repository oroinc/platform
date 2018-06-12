<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Represents the configuration of Data API sub-resource.
 */
class SubresourceConfig implements ConfigBagInterface
{
    /** @var bool|null */
    protected $exclude;

    /** @var array */
    protected $items = [];

    /** @var ActionConfig[] [action name => ActionConfig, ...] */
    protected $actions = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        $result = ConfigUtil::convertItemsToArray($this->items);
        if (null !== $this->exclude) {
            $result[ConfigUtil::EXCLUDE] = $this->exclude;
        }
        $actions = ConfigUtil::convertObjectsToArray($this->actions);
        if ($actions) {
            $result[ConfigUtil::ACTIONS] = $actions;
        }

        return $result;
    }

    /**
     * Indicates whether the sub-resource does not have a configuration.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return
            null === $this->exclude
            && empty($this->items)
            && empty($this->actions);
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->items = ConfigUtil::cloneItems($this->items);
        $this->actions = ConfigUtil::cloneObjects($this->actions);
    }

    /**
     * Gets the configuration for all actions.
     *
     * @return ActionConfig[] [action name => ActionConfig, ...]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Gets the configuration of the action.
     *
     * @param string $actionName
     *
     * @return ActionConfig|null
     */
    public function getAction($actionName)
    {
        if (!isset($this->actions[$actionName])) {
            return null;
        }

        return $this->actions[$actionName];
    }

    /**
     * Adds the configuration of the action.
     *
     * @param string            $actionName
     * @param ActionConfig|null $action
     *
     * @return ActionConfig
     */
    public function addAction($actionName, ActionConfig $action = null)
    {
        if (null === $action) {
            $action = new ActionConfig();
        }

        $this->actions[$actionName] = $action;

        return $action;
    }

    /**
     * Removes the configuration of the action.
     *
     * @param string $actionName
     */
    public function removeAction($actionName)
    {
        unset($this->actions[$actionName]);
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
     * Gets the class name of a target entity.
     *
     * @return string|null
     */
    public function getTargetClass()
    {
        return $this->get(ConfigUtil::TARGET_CLASS);
    }

    /**
     * Sets the class name of a target entity.
     *
     * @param string|null $className
     */
    public function setTargetClass($className)
    {
        if ($className) {
            $this->items[ConfigUtil::TARGET_CLASS] = $className;
        } else {
            unset($this->items[ConfigUtil::TARGET_CLASS]);
        }
    }

    /**
     * Indicates whether a target association represents "to-many" or "to-one" relationship.
     *
     * @return bool|null TRUE if a target association represents "to-many" relationship
     */
    public function isCollectionValuedAssociation()
    {
        if (!\array_key_exists(ConfigUtil::TARGET_TYPE, $this->items)) {
            return null;
        }

        return 'to-many' === $this->items[ConfigUtil::TARGET_TYPE];
    }

    /**
     * Indicates whether the type of a target association is set explicitly.
     *
     * @return bool
     */
    public function hasTargetType()
    {
        return $this->has(ConfigUtil::TARGET_TYPE);
    }

    /**
     * Gets the type of a target association.
     *
     * @return string|null Can be "to-one" or "to-many"
     */
    public function getTargetType()
    {
        return $this->get(ConfigUtil::TARGET_TYPE);
    }

    /**
     * Sets the type of a target association.
     *
     * @param string|null $targetType Can be "to-one" or "to-many"
     */
    public function setTargetType($targetType)
    {
        if ($targetType) {
            $this->items[ConfigUtil::TARGET_TYPE] = $targetType;
        } else {
            unset($this->items[ConfigUtil::TARGET_TYPE]);
        }
    }
}
