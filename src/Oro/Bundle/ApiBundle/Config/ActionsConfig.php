<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Traits;

class ActionsConfig
{
    use Traits\ConfigTrait;

    /** @var array */
    protected $items = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * Indicates whether the entity does not have a configuration.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Gets action configs.
     *
     * @param string $action
     *
     * @return array
     */
    public function getAction($action)
    {
        return $this->has($action) ? $this->get($action) : [];
    }

    /**
     * Returns false if given action is disabled for entity.
     *
     * @param $action
     *
     * @return bool
     */
    public function isActionEnabled($action)
    {
        return !($this->getAction($action)
            && isset($this->get($action)['exclude'])
            && $this->get($action)['exclude'] === true);
    }

    /**
     * @return array
     */
    public function getExcludedActions()
    {
        $result = [];
        foreach ($this->items as $action => $data) {
            if (isset($data['exclude']) && $data['exclude'] === true) {
                $result[] = $action;
            }
        }

        return $result;
    }

    /**
     * Returns false in case if acl protection was turned off for action.
     *
     * @param $action
     *
     * @return bool
     */
    public function isAclProtectedAction($action)
    {
        return !($this->has($action)
            && isset($this->get($action)['acl_resource'])
            && $this->get($action)['acl_resource'] === null);

    }

    /**
     * Returns acl resource for given action.
     *
     * @param $action
     *
     * @return null|string
     */
    public function getAclResource($action)
    {
        if (!$this->has($action)) {
            return;
        }

        $action = $this->get($action);
        if (!isset($action['acl_resource'])) {
            return;
        }

        return $action['acl_resource'];
    }
}
