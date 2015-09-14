<?php

namespace Oro\Bundle\ActivityListBundle\Model;

interface ActivityListDateProviderInterface
{
    /**
     * Get date from entity. Can be useful on SYNC with external servers
     *
     * @deprecated 1.8.0:1.10.0 Use getCreatedAt, getUpdatedAt instead
     * @param object $entity
     *
     * @return \DateTime
     */
    public function getDate($entity);

    /**
     * Can be updated date field on update activity entity
     *
     * @deprecated 1.8.0:1.10.0 Method is deprecated and will be removed
     * @see https://magecore.atlassian.net/browse/BAP-9014
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
