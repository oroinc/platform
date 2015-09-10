<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityEntityApiEntityManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;

class EmailActivityEntityApiEntityManager extends ActivityEntityApiEntityManager
{
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
        parent::__construct($om, $activityManager);
        $this->setClass($class);
        $this->securityTokenStorage = $securityTokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        $queryBuilder = $this->getListQueryBuilder($limit, $page, $criteria, $orderBy, $joins);

        /**
         * Need to exclude current user from result because of email context.
         * @see \Oro\Bundle\EmailBundle\Entity\Manager\EmailApiEntityManager::getEmailContext
         */
        if ($queryBuilder) {
            $currentUser = $this->securityTokenStorage->getToken()->getUser();
            // @todo: Filter aliases should be refactored in BAP-8979.
            $queryBuilder->andWhere(
                'NOT (id_1 = :userId AND sclr_2 =:userClass)'
            );
            $queryBuilder->setParameter('userId', $currentUser->getId());
            $queryBuilder->setParameter('userClass', ClassUtils::getClass($currentUser));
        }

        return $queryBuilder;
    }
}
