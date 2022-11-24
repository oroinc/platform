<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\NumberRangeFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class NumberRangeFilterTest extends WebTestCase
{
    private NumberRangeFilter $filter;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadUserData::class]);
        $this->filter = self::getContainer()->get('oro_filter.number_range_filter');
    }

    private function createQueryBuilder(string $alias): QueryBuilder
    {
        $doctrine = self::getContainer()->get('doctrine');

        return $doctrine->getRepository(User::class)->createQueryBuilder($alias);
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilterWithForm(string $filterName, array $filterData, array $expectedResult)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        $this->filter->init($filterName, ['enabled' => true, 'type' => 'number-range', 'data_name' => $filterName]);

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
    public function testFilterWithoutForm(string $filterName, array $filterData, array $expectedResult)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        $this->filter->init($filterName, ['enabled' => true, 'type' => 'number-range', 'data_name' => $filterName]);

        $data = $this->filter->prepareData($filterData);

        $ds = new OrmFilterDatasourceAdapter($qb);
        $this->filter->apply($ds, $data);

        $result = $ds->getQueryBuilder()->getQuery()->getResult();
        self::assertSame($expectedResult, $result);
    }

    public function filterProvider(): array
    {
        return [
            'BETWEEN x AND y'        => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type'      => NumberRangeFilterType::TYPE_BETWEEN,
                    'value'     => '5',
                    'value_end' => '25'
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2']
                ]
            ],
            'BETWEEN x AND NULL'     => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type'  => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => '15'
                ],
                'expectedResult' => [
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ]
            ],
            'BETWEEN NULL AND y'     => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type'      => NumberRangeFilterType::TYPE_BETWEEN,
                    'value_end' => '15'
                ],
                'expectedResult' => [
                    ['username' => 'u1']
                ]
            ],
            'NOT BETWEEN x AND y'    => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type'      => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                    'value'     => '11',
                    'value_end' => '22'
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u3']
                ]
            ],
            'NOT BETWEEN x AND NULL' => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type'  => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                    'value' => '20'
                ],
                'expectedResult' => [
                    ['username' => 'u1']
                ]
            ],
            'NOT BETWEEN NULL AND y' => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type'      => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                    'value_end' => '20'
                ],
                'expectedResult' => [
                    ['username' => 'u3']
                ]
            ]
        ];
    }
}
