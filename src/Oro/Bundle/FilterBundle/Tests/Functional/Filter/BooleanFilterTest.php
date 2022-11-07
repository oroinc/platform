<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\BooleanFilter;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class BooleanFilterTest extends WebTestCase
{
    private BooleanFilter $filter;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadUserData::class]);
        $this->filter = self::getContainer()->get('oro_filter.boolean_filter');
    }

    private function createQueryBuilder(string $alias): QueryBuilder
    {
        $doctrine = self::getContainer()->get('doctrine');

        return $doctrine->getRepository(User::class)->createQueryBuilder($alias);
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilterWithForm(array $filterData, array $expectedResult)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        $this->filter->init('enabled', ['enabled' => true, 'type' => 'boolean', 'data_name' => 'u.enabled']);

        $filterForm = $this->filter->getForm();
        $filterForm->submit($filterData);
        self::assertTrue($filterForm->isValid());
        self::assertTrue($filterForm->isSynchronized());
        $data = $filterForm->getData();

        $ds = new OrmFilterDatasourceAdapter($qb);
        $this->filter->apply($ds, $data);

        $result = $ds->getQueryBuilder()->getQuery()->getResult();
        self::assertSame($expectedResult, $result);
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilterWithoutForm(array $filterData, array $expectedResult)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        $this->filter->init('enabled', ['enabled' => true, 'type' => 'boolean', 'data_name' => 'u.enabled']);

        $data = $this->filter->prepareData($filterData);

        $ds = new OrmFilterDatasourceAdapter($qb);
        $this->filter->apply($ds, $data);

        $result = $ds->getQueryBuilder()->getQuery()->getResult();
        self::assertSame($expectedResult, $result);
    }

    public function filterProvider(): array
    {
        return [
            'True as numeric string'  => [
                'filterData'     => [
                    'value' => '1'
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u3']
                ]
            ],
            'False as numeric string' => [
                'filterData'     => [
                    'value' => '2'
                ],
                'expectedResult' => [
                    ['username' => 'u2']
                ]
            ],
            'True as integer'         => [
                'filterData'     => [
                    'value' => 1
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u3']
                ]
            ],
            'False as integer'        => [
                'filterData'     => [
                    'value' => 2
                ],
                'expectedResult' => [
                    ['username' => 'u2']
                ]
            ]
        ];
    }
}
