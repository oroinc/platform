<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Oro\Bundle\IntegrationBundle\Exception\RuntimeException;

trait EntityStateManagerTrait
{
    /**
     * @var EntityStateManager
     */
    protected $entityStateManager;

    /**
     * @param EntityStateManager $entityStateManager
     */
    public function setEntityStateManager(EntityStateManager $entityStateManager)
    {
        $this->entityStateManager = $entityStateManager;
    }

    /**
     * @return EntityStateManager $entityStateManager
     */
    public function getEntityStateManager()
    {
        if (!$this->entityStateManager) {
            throw new RuntimeException('Entity State manager must be set');
        }

        return $this->entityStateManager;
    }
}
