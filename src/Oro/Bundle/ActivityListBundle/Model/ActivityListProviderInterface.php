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
     *
     * @return string
     */
    public function getSubject($entity);

    /**
     * @param object $entity
     *
     * @return array
     */
    public function getData($entity);

    /**
     * @return string
     */
    public function getBriefTemplate();

    /**
     * @return string
     */
    public function getFullTemplate();

    /**
     * @param $entity
     *
     * @return integer
     */
    public function getActivityId($entity);

    /**
     * Check if provider supports given entity
     *
     * @param object|string $entity
     *
     * @return bool
     */
    public function isApplicable($entity);
}
