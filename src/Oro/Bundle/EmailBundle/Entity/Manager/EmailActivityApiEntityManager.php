<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class EmailActivityApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /**
     * @param string          $class
     * @param ObjectManager   $om
     * @param ActivityManager $activityManager
     */
    public function __construct($class, ObjectManager $om, ActivityManager $activityManager)
    {
        parent::__construct($class, $om);
        $this->activityManager = $activityManager;
    }

    /**
     * Returns id of an email entity corresponding given criteria
     *
     * @param Criteria|array $criteria
     * @param array          $joins
     *
     * @return int|null
     */
    public function findEmailId($criteria, $joins)
    {
        $criteria = $this->normalizeCriteria($criteria);

        $qb = $this->getRepository()->createQueryBuilder('e')
            ->select('partial e.{id}')
            ->setMaxResults(2);
        $this->applyJoins($qb, $joins);

        $qb->addCriteria($criteria);

        /** @var Email[] $entity */
        $entity = $qb->getQuery()->getResult();
        if (!$entity || count($entity) > 1) {
            return null;
        }

        $this->checkFoundEntity($entity[0]);

        return $entity[0]->getId();
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
