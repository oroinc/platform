<?php

namespace Oro\Bundle\IntegrationBundle\EventListener;

use Oro\Bundle\IntegrationBundle\Manager\EntityStateManager;

class EntityStateClearListener
{
    /**
     * @var EntityStateManager
     */
    protected $entityStateManager;

    /**
     * @param EntityStateManager $entityStateManager
     */
    public function __construct(EntityStateManager $entityStateManager)
    {
        $this->entityStateManager = $entityStateManager;
    }

    /**
     * Clear entity state collections on doctrine clear event
     */
    public function onClear()
    {
        $this->entityStateManager->clear();
    }

    /**
     * Flush entity state collections after write done
     */
    public function afterCommit()
    {
        $this->entityStateManager->flush();
    }
}
