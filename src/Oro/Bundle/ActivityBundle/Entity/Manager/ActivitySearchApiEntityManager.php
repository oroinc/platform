<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class ActivitySearchApiEntityManager extends ApiEntityManager
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
     * Returns the list of fields responsible to store activity associations for the given activity entity type
     *
     * @return array
     */
    public function getAssociations()
    {
        return $this->activityManager->getActivityTargets($this->class);
    }
}
