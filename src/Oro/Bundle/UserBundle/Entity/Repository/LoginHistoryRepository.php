<?php

namespace Oro\Bundle\UserBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\LoginHistory;

class LoginHistoryRepository extends EntityRepository
{
    /**
     * @param  UserInterface $user
     * @return int
     */
    public function countUserDailyFailedLogins(UserInterface $user)
    {
        $to = new \DateTime('now', new \DateTimezone('UTC'));
        $from = clone $to;
        $from->modify('-1 day');

        return $this->createQueryBuilder('lh')
            ->select('COUNT(lh)')
            ->where('lh.user = :user')
            ->andWhere('lh.successful = :successful')
            ->andWhere('lh.createdAt BETWEEN :from AND :to')
            ->setParameters([
                'user' => $user,
                'successful' => false,
                'from' => $from,
                'to' => $to
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param  UserInterface $user
     * @return int
     */
    public function countUserCumulativeFailedLogins(UserInterface $user)
    {
        return $this->createQueryBuilder('lh')
            ->select('COUNT(lh)')
            ->where('lh.user = :user')
            ->andWhere('lh.successful = :successful')
            ->setParameters([
                'user' => $user,
                'successful' => false,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
