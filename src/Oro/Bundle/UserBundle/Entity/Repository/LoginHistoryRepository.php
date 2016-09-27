<?php

namespace Oro\Bundle\UserBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\LoginHistory;

class LoginHistoryRepository extends EntityRepository
{
    /**
     * @param UserInterface $user
     * @param string $class
     *
     * @return LoginHistory
     */
    public function getByUserAndProviderClass(UserInterface $user, $class)
    {
        return $this->createQueryBuilder('lh')
            ->select('lh')
            ->where('lh.user = :user AND lh.providerClass = :providerClass')
            ->setParameters([
                'user' => $user,
                'providerClass' => $class
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param UserInterface $user
     *
     * @return array
     */
    public function getByUserAndMaxFailedDailyAttempts(UserInterface $user)
    {
        return $this->createQueryBuilder('lh')
            ->select('lh')
            ->where('lh.user = :user')
            ->orderBy('lh.failedDailyAttempts', 'DESC')
            ->setMaxResults(1)
            ->setParameters([
                'user' => $user
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * @param UserInterface $user
     *
     * @return mixed
     */
    public function getByUserAndMaxFailedAttempts(UserInterface $user)
    {
        return $this->createQueryBuilder('lh')
            ->select('lh')
            ->where('lh.user = :user')
            ->orderBy('lh.failedAttempts', 'DESC')
            ->setMaxResults(1)
            ->setParameters([
                'user' => $user
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * @param UserInterface $user
     *
     * @return mixed
     */
    public function getByUser(UserInterface $user)
    {
        return $this->createQueryBuilder('lh')
            ->select('lh')
            ->where('lh.user = :user')
            ->setParameters([
                'user' => $user
            ])
            ->getQuery()
            ->execute();
    }
}
