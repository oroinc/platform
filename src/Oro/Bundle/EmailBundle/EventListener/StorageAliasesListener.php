<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EntityPaginationBundle\Event\StorageAliasesEvent;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class StorageAliasesListener
{

    /**
     * @var DatagridConfiguration
     */
    protected $config;

    /**
     * StorageAliaseListener constructor
     * Set container to get option parameters
     * for aliases
     * .
     * @param DatagridConfiguration $config
     */
    public function __construct(DatagridConfiguration $config)
    {
        $this->config = $config;
    }

    /**
     * Added alias for storage alis event
     *
     * @param StorageAliasesEvent $event
     */
    public function onStorageAliaseEvent(StorageAliasesEvent $event)
    {
        $targetClass = $this->config->offsetGetByPath(StorageAliasesEvent::STORAGE_ALIASES_TARGET_CLASS_PATH);
        $alias = $this->config->offsetGetByPath(StorageAliasesEvent::STORAGE_ALIASES_TARGET_CLASS_ALIAS_PATH);

        if ($targetClass !== null && $alias !== null) {
            $event->setAliases($targetClass, $alias);
        }
    }
}