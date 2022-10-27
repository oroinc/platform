<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Represents the configuration of API sub-resource.
 */
class SubresourceConfig
{
    private ?bool $exclude = null;
    private array $items = [];
    /** @var ActionConfig[] [action name => ActionConfig, ...] */
    private array $actions = [];

    /**
     * Gets a native PHP array representation of the configuration.
     */
    public function toArray(): array
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
     */
    public function isEmpty(): bool
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
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Gets the configuration of the action.
     */
    public function getAction(string $actionName): ?ActionConfig
    {
        return $this->actions[$actionName] ?? null;
    }

    /**
     * Adds the configuration of the action.
     */
    public function addAction(string $actionName, ActionConfig $action = null): ActionConfig
    {
        if (null === $action) {
            $action = new ActionConfig();
        }

        $this->actions[$actionName] = $action;

        return $action;
    }

    /**
     * Removes the configuration of the action.
     */
    public function removeAction(string $actionName): void
    {
        unset($this->actions[$actionName]);
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
     * Gets the class name of a target entity.
     */
    public function getTargetClass(): ?string
    {
        return $this->get(ConfigUtil::TARGET_CLASS);
    }

    /**
     * Sets the class name of a target entity.
     */
    public function setTargetClass(?string $className): void
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
     * @return bool TRUE if a target association represents "to-many" relationship; otherwise, FALSE
     */
    public function isCollectionValuedAssociation(): bool
    {
        return
            \array_key_exists(ConfigUtil::TARGET_TYPE, $this->items)
            && ConfigUtil::TO_MANY === $this->items[ConfigUtil::TARGET_TYPE];
    }

    /**
     * Indicates whether the type of a target association is set explicitly.
     */
    public function hasTargetType(): bool
    {
        return $this->has(ConfigUtil::TARGET_TYPE);
    }

    /**
     * Gets the type of a target association.
     *
     * @return string|null Can be "to-one" or "to-many"
     */
    public function getTargetType(): ?string
    {
        return $this->get(ConfigUtil::TARGET_TYPE);
    }

    /**
     * Sets the type of a target association.
     *
     * @param string|null $targetType Can be "to-one" or "to-many"
     */
    public function setTargetType(?string $targetType): void
    {
        if ($targetType) {
            $this->items[ConfigUtil::TARGET_TYPE] = $targetType;
        } else {
            unset($this->items[ConfigUtil::TARGET_TYPE]);
        }
    }
}
