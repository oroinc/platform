<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Traits;

class ActionsConfig
{
    use Traits\ConfigTrait;

    /** Indicates that action excluded or not */
    const EXCLUDE = 'exclude';

    /** ACL resource for action */
    const ACL_RESOURCE = 'acl_resource';

    /** Delete handler for delete action */
    const DELETE_HANDLER = 'delete_handler';

    /** Default delete handler service that will be used in case if delete_handler parameter is not set */
    const DEFAULT_DELETE_HANDLER = 'oro_soap.handler.delete';

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
    public function isEnabled($action)
    {
        return !($this->getAction($action)
            && isset($this->get($action)[self::EXCLUDE])
            && $this->get($action)[self::EXCLUDE] === true);
    }

    /**
     * @return array
     */
    public function getExcluded()
    {
        $result = [];
        foreach ($this->items as $action => $data) {
            if (isset($data[self::EXCLUDE]) && $data[self::EXCLUDE] === true) {
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
    public function isAclProtected($action)
    {
        return !($this->has($action)
            && isset($this->get($action)[self::ACL_RESOURCE])
            && $this->get($action)[self::ACL_RESOURCE] === null);

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
        if (!isset($action[self::ACL_RESOURCE])) {
            return;
        }

        return $action[self::ACL_RESOURCE];
    }

    /**
     * Returns delete handler service name for delete action.
     *
     * @return string
     */
    public function getDeleteHandler()
    {
        $deleteAction = $this->getAction('delete');
        return array_key_exists(self::DELETE_HANDLER, $deleteAction)
            ? $deleteAction[self::DELETE_HANDLER]
            : self::DEFAULT_DELETE_HANDLER;
    }
}
