<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * Email owner provider chain
 */
class EmailProvider
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getNewEmails(User $user, $limit)
    {
        $qb = $this->em->getRepository('OroEmailBundle:Email')->createQueryBuilder('e')
                ->leftJoin('e.emailUsers', 'eu')
                ->where('eu.organization = :organizationId')
                ->andWhere('eu.owner = :ownerId')
                ->andWhere('eu.seen = 0')
                ->orderBy('e.sentAt', 'DESC')
                ->setParameter('organizationId', $user->getOrganization()->getId())
                ->setParameter('ownerId', $user->getId())
                ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getCountNewEmails(User $user)
    {
        return $this->em->getRepository('OroEmailBundle:Email')->createQueryBuilder('e')
            ->select('COUNT(e)')
            ->leftJoin('e.emailUsers', 'eu')
            ->where('eu.organization = :organizationId')
            ->andWhere('eu.owner = :ownerId')
            ->andWhere('eu.seen = 0')
            ->setParameter('organizationId', $user->getOrganization()->getId())
            ->setParameter('ownerId', $user->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
