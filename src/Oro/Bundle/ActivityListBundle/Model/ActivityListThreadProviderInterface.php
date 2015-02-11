<?php

namespace Oro\Bundle\ActivityListBundle\Model;

interface ActivityListThreadProviderInterface
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
