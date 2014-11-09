<?php

namespace Oro\Bundle\ActivityListBundle\Model;

interface ActivityListProviderInterface
{
    /**
     * return pairs of class name and id,
     *
     * @return array
     */
    public function getTargets($entity);

    /**
     * returns an array of supported target entity classes for activity
     *
     * @return string[]
     */
    public function getTargetEntityClasses();

    /**
     * returns a class name of entity for which we monitor changes
     *
     * @return string
     */
    public function getActivityClass();

    /**
     * @param object $entity
     * @return string
     */
    public function getSubject($entity);

    public function getData($entity);

    public function getTemplate();

    public function getActivityId($entity);

    /**
     * Check if provider supports given entity
     *
     * @param $entity
     * @return bool
     */
    public function isApplicable($entity);
}
