<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Traits;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ActionsConfig
{
    use Traits\ActionsTrait;

    /** @var ActionConfig[] [action name => ActionConfig, ...] */
    protected $actions = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        return ConfigUtil::convertObjectsToArray($this->actions);
    }

    /**
     * Indicates whether there is a configuration at least one action.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->actions);
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->actions = ConfigUtil::cloneObjects($this->actions);
    }
}
