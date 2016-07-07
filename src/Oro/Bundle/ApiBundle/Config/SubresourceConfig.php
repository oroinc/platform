<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Traits;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class SubresourceConfig
{
    use Traits\ConfigTrait;
    use Traits\ExcludeTrait;
    use Traits\AssociationTargetTrait;
    use Traits\ActionsTrait;

    /** a flag indicates whether the sub-resource should not be available for the entity */
    const EXCLUDE = ConfigUtil::EXCLUDE;

    /** the class name of a target entity */
    const TARGET_CLASS = EntityDefinitionFieldConfig::TARGET_CLASS;

    /**
     * the type of a target association, can be "to-one" or "to-many",
     * also "collection" can be used in Resources/config/oro/api.yml file as an alias for "to-many"
     */
    const TARGET_TYPE = EntityDefinitionFieldConfig::TARGET_TYPE;

    /** a list of actions */
    const ACTIONS = ConfigUtil::ACTIONS;

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
        $result = $this->convertItemsToArray();
        $actions = ConfigUtil::convertObjectsToArray($this->actions);
        if (!empty($actions)) {
            $result[self::ACTIONS] = $actions;
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
            empty($this->items)
            && empty($this->actions);
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->cloneItems();
        $this->actions = ConfigUtil::cloneObjects($this->actions);
    }
}
