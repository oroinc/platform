<?php

namespace Oro\Bundle\ActivityListBundle\Model;

interface ActivityListDateProviderInterface
{
    /**
     * Get date from entity. Can be useful on SYNC with external servers
     *
     * @deprecated method is deprecated
     * @param object $entity
     *
     * @return \DateTime
     */
    public function getDate($entity);

    /**
     * Can be updated date field on update activity entity
     *
     * @deprecated method is deprecated
     * @return bool
     */
    public function isDateUpdatable();

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
