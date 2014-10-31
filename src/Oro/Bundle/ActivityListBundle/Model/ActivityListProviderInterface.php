<?php

namespace Oro\Bundle\ActivityListBundle\Model;


interface ActivityListProviderInterface
{
    /**
     * return pairs of class name and id,
     *
     * @return array
     */
    public function getTargets();

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

    public function getBriefData($entity);

    public function getData($entity);

    public function getBriefTemplate();

    public function getFullTemplate();

    public function getActivityId($entity);

    /**
     * Check if provider supports given entity
     *
     * @param $entity
     * @return bool
     */
    public function isApplicable($entity);
}
