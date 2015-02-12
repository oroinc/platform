<?php

namespace Oro\Bundle\ActivityListBundle\Model;

interface ActivityListGroupProviderInterface
{
    /**
     * Get thread head state from entity.
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isHead($entity);
}
