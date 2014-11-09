<?php

namespace Oro\Bundle\ActivityListBundle\Model;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

interface ActivityListProviderInterface
{
    /**
     * return pairs of class name and id,
     *
     * @return array
     */
    public function getTargets($entity);

    /**
     * returns true if given $configId is supported by activity
     *
     * @param ConfigIdInterface $configId
     * @param ConfigManager     $configManager
     *
     * @return bool
     */
    public function isApplicableTarget(ConfigIdInterface $configId, ConfigManager $configManager);

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
