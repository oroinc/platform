<?php

namespace Oro\Bundle\ActivityListBundle\Model;

interface ActivityListDateProviderInterface
{
    /**
     * Get created at from entity.
     *
     * @param object $entity
     * @return \DateTime|null
     */
    public function getCreatedAt($entity);

    /**
     * Get updated at from entity.
     *
     * @param object $entity
     * @return \DateTime|null
     */
    public function getUpdatedAt($entity);
}
