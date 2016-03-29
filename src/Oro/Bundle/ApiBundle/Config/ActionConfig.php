<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Traits;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ActionConfig
{
    use Traits\ConfigTrait;
    use Traits\ExcludeTrait;
    use Traits\AclResourceTrait;
    use Traits\DescriptionTrait;
    use Traits\MaxResultsTrait;
    use Traits\StatusCodesTrait;

    /** a flag indicates whether the action should not be available for the entity */
    const EXCLUDE = ConfigUtil::EXCLUDE;

    /** the name of ACL resource */
    const ACL_RESOURCE = EntityDefinitionConfig::ACL_RESOURCE;

    /** the entity description for the action  */
    const DESCRIPTION = EntityDefinitionConfig::DESCRIPTION;

    /** the maximum number of items in the result */
    const MAX_RESULTS = EntityDefinitionConfig::MAX_RESULTS;

    /** the maximum number of items in the result */
    const STATUS_CODES = EntityDefinitionConfig::STATUS_CODES;

    /** @var array */
    protected $items = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        $result = $this->items;

        $keys = array_keys($result);
        foreach ($keys as $key) {
            $value = $result[$key];
            if (is_object($value) && method_exists($value, 'toArray')) {
                $result[$key] = $value->toArray();
            }
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
        return empty($this->items);
    }

    /**
     * Make a deep copy of object.
     */
    public function __clone()
    {
        $this->items = array_map(
            function ($value) {
                return is_object($value) ? clone $value : $value;
            },
            $this->items
        );
    }
}
