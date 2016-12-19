<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\Filter;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;

class HasNotDuplicateRelation implements FixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function createData(EntityManager $em)
    {
        $user1 = (new User())
            ->setUsername('u1')
            ->setEmail('u1@example.com')
            ->setPassword('u1')
            ->addEmail(
                (new Email())
                    ->setEmail('duplicate@example.com')
            );
        $user2 = (new User())
            ->setUsername('u2')
            ->setEmail('u2@example.com')
            ->setPassword('u2')
            ->addEmail(
                (new Email())
                    ->setEmail('duplicate@example.com')
            );
        $user3 = (new User())
            ->setUsername('u3')
            ->setEmail('u3@example.com')
            ->setPassword('u3')
            ->addEmail(
                (new Email())
                    ->setEmail('different@example.com')
            );

        $em->persist($user1);
        $em->persist($user2);
        $em->persist($user3);
    }

    /**
     * {@inheritdoc}
     */
    public function createFilterDatasourceAdapter(EntityManager $em)
    {
        $qb = $em->getRepository(User::class)->createQueryBuilder('u');
        $qb
            ->select('u.username')
            ->join('u.emails', 'e')
            ->orderBy('u.username');
        $qb->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        return new OrmFilterDatasourceAdapter($qb);
    }

    /**
     * {@inheritdoc}
     */
    public function submitFilter(FilterInterface $filter)
    {
        $filter->init('duplicate', ['data_name' => 'e.email']);
        $filterForm = $filter->getForm();
        $filterForm->submit(['value' => BooleanFilterType::TYPE_NO]);
    }

    /**
     * {@inheritdoc}
     */
    public function assert(\PHPUnit_Framework_Assert $assertions, array $actualData)
    {
        $assertions->assertEquals(
            ['u3'],
            array_map('current', $actualData)
        );
    }
}
