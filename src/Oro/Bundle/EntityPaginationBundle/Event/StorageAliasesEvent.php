<?php

namespace Oro\Bundle\EntityPaginationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class StorageAliasesEvent extends Event
{
    const EVENT_NAME = 'oro_entity_pagination.storage_aliases';
    const STORAGE_ALIASES_TARGET_CLASS_PATH = '[options][storage][aliases][target_class]';
    const STORAGE_ALIASES_TARGET_CLASS_ALIAS_PATH = '[options][storage][aliases][alias]';


    /** @var array */
    protected $aliases = array();

    /**
     * Return aliases config array
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Set the alias for target class
     *
     * @param string $targetClass
     * @param string $alias
     */
    public function setAliases($targetClass, $alias)
    {
        $this->aliases[$targetClass] = $alias;
    }

}