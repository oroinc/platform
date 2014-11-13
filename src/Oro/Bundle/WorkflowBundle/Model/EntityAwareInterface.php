<?php

namespace Oro\Bundle\WorkflowBundle\Model;

interface EntityAwareInterface
{
    /**
     * Get related entity.
     *
     * @return object
     */
    public function getEntity();
}
