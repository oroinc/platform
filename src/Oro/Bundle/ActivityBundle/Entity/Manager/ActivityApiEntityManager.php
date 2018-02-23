<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class ActivityApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /**
     * @param ObjectManager   $om
     * @param ActivityManager $activityManager
     */
    public function __construct(ObjectManager $om, ActivityManager $activityManager)
    {
        parent::__construct(null, $om);
        $this->activityManager = $activityManager;
    }

    /**
     * Returns the list of FQCN of all activity entities
     *
     * @return string[]
     */
    public function getActivityTypes()
    {
        return array_map(
            function ($class) {
                return ['entity' => $class];
            },
            $this->activityManager->getActivityTypes()
        );
    }

    /**
     * Returns the list of FQCN of all activity entities
     *
     * @return string[]
     */
    public function getActivityTargetTypes()
    {
        return array_map(
            function ($class) {
                return ['entity' => $class];
            },
            array_keys($this->activityManager->getActivityTargets($this->class))
        );
    }
}
