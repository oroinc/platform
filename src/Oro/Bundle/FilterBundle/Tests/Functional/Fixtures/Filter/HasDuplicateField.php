<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\Filter;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\UserBundle\Entity\User;

class HasDuplicateField implements FixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function createData(EntityManager $em)
    {
        $user1 = (new User())
            ->setFirstName('Duplicate')
            ->setUsername('u1')
            ->setEmail('u1@example.com')
            ->setPassword('u1');
        $user2 = (new User())
            ->setFirstName('Duplicate')
            ->setUsername('u2')
            ->setEmail('u2@example.com')
            ->setPassword('u2');
        $user3 = (new User())
            ->setFirstName('Different')
            ->setUsername('u3')
            ->setEmail('u3@example.com')
            ->setPassword('u3');

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
            ->orderBy('u.username');
        $qb->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        return new OrmFilterDatasourceAdapter($qb);
    }

    /**
     * {@inheritdoc}
     */
    public function submitFilter(FilterInterface $filter)
    {
        $filter->init('duplicate', ['data_name' => 'u.firstName']);
        $filterForm = $filter->getForm();
        $filterForm->submit(['value' => BooleanFilterType::TYPE_YES]);
    }

    /**
     * {@inheritdoc}
     */
    public function assert(\PHPUnit_Framework_Assert $assertions, array $actualData)
    {
        $assertions->assertEquals(
            ['u1', 'u2'],
            array_map('current', $actualData)
        );
    }
}
