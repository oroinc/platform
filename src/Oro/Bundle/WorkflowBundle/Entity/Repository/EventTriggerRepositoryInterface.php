<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;

/**
 * Defines the contract for repositories managing event trigger entities.
 *
 * This interface extends the standard Doctrine ObjectRepository to provide workflow-specific
 * methods for retrieving event triggers that are available for use in the system.
 */
interface EventTriggerRepositoryInterface extends ObjectRepository
{
    /**
     * @return EventTriggerInterface[]
     */
    public function getAvailableEventTriggers();
}
