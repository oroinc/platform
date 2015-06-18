<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class ActivityEntityApiEntityManager extends ApiEntityManager
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
     * {@inheritdoc}
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        return $this->activityManager->getActivityTargetsQueryBuilder(
            $this->class,
            $criteria,
            $joins,
            $limit,
            $page,
            $orderBy
        );
    }
}
