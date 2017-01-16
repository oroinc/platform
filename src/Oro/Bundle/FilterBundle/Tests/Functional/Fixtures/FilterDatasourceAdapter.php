<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Fixtures;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;

class FilterDatasourceAdapter extends OrmFilterDatasourceAdapter
{
    public function __construct(ManagerRegistry $registry)
    {
        $em = $registry->getManagerForClass(User::class);
        $this->loadData($em);
        $qb = $this->getQB($em);
        parent::__construct($qb);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadData(EntityManager $em)
    {
        $user1 = (new User())
            ->setUsername('u1')
            ->setEmail('u1@example.com')
            ->setPassword('u1');
        $user2 = (new User())
            ->setUsername('u2')
            ->setEmail('u2@example.com')
            ->setPassword('u2');
        $user3 = (new User())
            ->setUsername('u3')
            ->setEmail('u3@example.com')
            ->setPassword('u3');

        $em->persist($user1);
        $em->persist($user2);
        $em->persist($user3);
        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    protected function getQB(EntityManager $em)
    {
        $qb = $em->getRepository(User::class)->createQueryBuilder('u');
        $qb
            ->select('u.username')
            ->orderBy('u.username');
        $qb->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        return $qb;
    }
}
