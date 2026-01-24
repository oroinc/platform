<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Component\Action\Model\AbstractStorage as ComponentAbstractStorage;

/**
 * Stores data for process execution with entity awareness.
 *
 * This class extends {@see AbstractStorage} to provide a container for process data values
 * and implements {@see EntityAwareInterface} to provide access to the related entity.
 */
class ProcessData extends ComponentAbstractStorage implements EntityAwareInterface
{
    #[\Override]
    public function getEntity()
    {
        return $this->get('data');
    }
}
