<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Query;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class EmailActivityApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var TokenStorageInterface */
    protected $securityTokenStorage;

    /**
     * @param string                $class
     * @param ObjectManager         $om
     * @param ActivityManager       $activityManager
     * @param TokenStorageInterface $securityTokenStorage
     */
    public function __construct(
        $class,
        ObjectManager $om,
        ActivityManager $activityManager,
        TokenStorageInterface $securityTokenStorage
    ) {
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
        $queryBuilder = $this->activityManager->getActivityTargetsQueryBuilder(
            $this->class,
            $criteria,
            $joins,
            $limit,
            $page,
            $orderBy
        );

        /**
         * Need to exclude current user from result because of email context.
         * @see \Oro\Bundle\EmailBundle\Entity\Manager\EmailApiEntityManager::getEmailContext
         */
        if ($queryBuilder) {
            $currentUser = $this->securityTokenStorage->getToken()->getUser();
            $queryBuilder->andWhere(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->neq('id', $currentUser->getId()),
                    $queryBuilder->expr()->neq('entity', ClassUtils::getClass($currentUser))
                )
            );
        }

        return $queryBuilder;

    }
}
