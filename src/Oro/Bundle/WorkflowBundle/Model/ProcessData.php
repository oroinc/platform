<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Component\ConfigExpression\Model\AbstractStorage;

class ProcessData extends AbstractStorage implements EntityAwareInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntity()
    {
        return $this->get('data');
    }
}
