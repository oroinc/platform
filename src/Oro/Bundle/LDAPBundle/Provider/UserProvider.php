<?php

namespace Oro\Bundle\LDAPBundle\Provider;

use Iterator;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

use Oro\Bundle\LDAPBundle\Model\User;

class UserProvider
{
    /** @var Registry */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return Iterator
     */
    public function getUsersIterator()
    {
        $qb = $this->getUserRepository()->createQueryBuilder('u');

        return $qb->getQuery()->iterate();
    }

    /**
     * @return int
     */
    public function getNumberOfUsers()
    {
        $qb = $this->getUserRepository()->createQueryBuilder('u');
        $paginator = new Paginator($qb);

        return $paginator->count();
    }

    /**
     * @param string[] $usernames
     *
     * @return User[]
     */
    public function findUsersByUsernames(array $usernames = [])
    {
        if (!$usernames) {
            return [];
        }

        return $this->createUsernamesQb($usernames)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string[] $usernames
     *
     * @return int
     */
    public function getNumberOfUsersByUsernames(array $usernames = [])
    {
        if (!$usernames) {
            return 0;
        }
        
        $paginator = new Paginator($this->createUsernamesQb($usernames));

        return $paginator->count();
    }

    /**
     * @param string[] $usernames
     *
     * @return QueryBuilder
     */
    protected function createUsernamesQb(array $usernames)
    {
        $qb = $this->getUserRepository()->createQueryBuilder('u');
        $qb
            ->andWhere($qb->expr()->in('u.username', ':usernames'))
            ->setParameter('usernames', $usernames);

        return $qb;
    }

    /**
     * @return EntityRepository
     */
    protected function getUserRepository()
    {
        return $this->registry->getRepository('OroUserBundle:User');
    }
}
