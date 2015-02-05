<?php

namespace Oro\Bundle\ActivityListBundle\Model;

interface ActivityListDateProviderInterface
{
    /**
     * Get date from entity. Can be useful on SYNC with external servers
     *
     * @param object $entity
     *
     * @return \DateTime
     */
    public function getDate($entity);
}
