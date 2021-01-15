<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;

interface EventTriggerRepositoryInterface extends ObjectRepository
{
    /**
     * @return EventTriggerInterface[]
     */
    public function getAvailableEventTriggers();
}
