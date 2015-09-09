<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Query;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class ActivityEntityApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var TokenStorageInterface */
    protected $securityTokenStorage;

    /**
     * @param ObjectManager         $om
     * @param ActivityManager       $activityManager
     * @param TokenStorageInterface $securityTokenStorage
     */
    public function __construct(
        ObjectManager $om,
        ActivityManager $activityManager,
        TokenStorageInterface $securityTokenStorage
    ) {
        parent::__construct(null, $om);
        $this->activityManager = $activityManager;
        $this->securityTokenStorage = $securityTokenStorage;
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
            // @todo: Filter aliases should be refactored in BAP-8979.
            $queryBuilder->andWhere(
                $queryBuilder->expr()->andX(
                    //Filter by entity id
                    $queryBuilder->expr()->neq('id_1', $currentUser->getId()),
                    //Filter by entity class
                    $queryBuilder->expr()->neq(
                        'sclr_2',
                        sprintf("'%s'", str_replace('\\', '\\\\\\\\', ClassUtils::getClass($currentUser)))
                    )
                )
            );
        }

        return $queryBuilder;
    }
}
