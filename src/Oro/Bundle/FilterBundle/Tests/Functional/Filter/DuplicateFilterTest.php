<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\DuplicateFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadDuplicateUserData;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadDuplicateUserRelationData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class DuplicateFilterTest extends WebTestCase
{
    /** @var DuplicateFilter */
    protected $filter;

    public function setUp()
    {
        $this->initClient();
        $this->filter = $this->getContainer()->get('oro_filter.duplicate_filter');
    }

    /**
     * @dataProvider duplicateDataProvider
     *
     * @param int $filterType
     * @param array $expected
     */
    public function testDuplicateField($filterType, array $expected)
    {
        $this->loadFixtures([LoadDuplicateUserData::class]);

        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        $ds = new OrmFilterDatasourceAdapter($qb);

        $filterForm = $this->filter->getForm();
        $filterForm->submit(['value' => $filterType]);

        $this->assertTrue($filterForm->isValid());

        $this->filter->init('duplicate', ['data_name' => 'u.firstName']);
        $this->filter->apply($ds, $filterForm->getData());

        $qb = $ds->getQueryBuilder();

        $actualData = $qb->getQuery()->getResult();
        $this->assertEquals($expected, array_map('current', $actualData));

        $dql = $qb->getDQL();
        $this->assertNotContains('EXISTS(', $dql);
        $this->assertContains('GROUP BY ', $dql);
    }

    /**
     * @dataProvider duplicateDataProvider
     *
     * @param int $filterType
     * @param array $expected
     */
    public function testDuplicateRelation($filterType, array $expected)
    {
        $this->loadFixtures([LoadDuplicateUserRelationData::class]);

        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->join('u.emails', 'e')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        $ds = new OrmFilterDatasourceAdapter($qb);

        $filterForm = $this->filter->getForm();
        $filterForm->submit(['value' => $filterType]);

        $this->assertTrue($filterForm->isValid());

        $this->filter->init('duplicate', ['data_name' => 'e.email']);
        $this->filter->apply($ds, $filterForm->getData());

        $qb = $ds->getQueryBuilder();

        $actualData = $qb->getQuery()->getResult();
        $this->assertEquals($expected, array_map('current', $actualData));
        $dql = $qb->getDQL();
        $this->assertContains('EXISTS(', $dql);
        $this->assertContains('GROUP BY ', $dql);
    }

    /**
     * @return array
     */
    public function duplicateDataProvider()
    {
        return [
            'has duplicate' => [
                'filterType' => BooleanFilterType::TYPE_YES,
                'expected' => ['u1', 'u2']
            ],
            'has no duplicate' => [
                'filterType' => BooleanFilterType::TYPE_NO,
                'expected' => ['u3']
            ]
        ];
    }

    /**
     * @param string $alias
     * @return QueryBuilder
     */
    protected function createQueryBuilder($alias)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $objectManager = $doctrine->getManagerForClass(User::class);
        $repository = $objectManager->getRepository(User::class);

        return $repository->createQueryBuilder($alias);
    }
}
