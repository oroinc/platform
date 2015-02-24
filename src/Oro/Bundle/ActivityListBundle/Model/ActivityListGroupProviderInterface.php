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

    /**
     * Get Grouped Entities by Activity Entity
     *
     * @param object $entity
     * @return array
     */
    public function getGroupedEntities($entity);

    /**
     * Get Grouped Template
     *
     * @return string
     */
    public function getGroupedTemplate();
}
