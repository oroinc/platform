<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\NumberFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class NumberFilterTest extends WebTestCase
{
    private NumberFilter $filter;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadUserData::class]);
        $this->filter = self::getContainer()->get('oro_filter.number_filter');
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

        $this->filter->init($filterName, ['enabled' => true, 'type' => 'number', 'data_name' => $filterName]);

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

        $this->filter->init($filterName, ['enabled' => true, 'type' => 'number', 'data_name' => $filterName]);

        $data = $this->filter->prepareData($filterData);

        $ds = new OrmFilterDatasourceAdapter($qb);
        $this->filter->apply($ds, $data);

        $result = $ds->getQueryBuilder()->getQuery()->getResult();
        self::assertSame($expectedResult, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function filterProvider(): array
    {
        return [
            'Filter "not empty"'         => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type' => FilterUtility::TYPE_NOT_EMPTY
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ]
            ],
            'Filter "empty"'             => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type' => FilterUtility::TYPE_EMPTY
                ],
                'expectedResult' => []
            ],
            'Filter "equal"'             => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type'  => NumberFilterType::TYPE_EQUAL,
                    'value' => '10'
                ],
                'expectedResult' => [
                    ['username' => 'u1']
                ]
            ],
            'Filter "not equal"'         => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type'  => NumberFilterType::TYPE_NOT_EQUAL,
                    'value' => '10'
                ],
                'expectedResult' => [
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ]
            ],
            'Filter "equals or greater"' => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type'  => NumberFilterType::TYPE_GREATER_EQUAL,
                    'value' => '20'
                ],
                'expectedResult' => [
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ]
            ],
            'Filter "greater"'           => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type'  => NumberFilterType::TYPE_GREATER_THAN,
                    'value' => '20'
                ],
                'expectedResult' => [
                    ['username' => 'u3']
                ]
            ],
            'Filter "equals or less"'    => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type'  => NumberFilterType::TYPE_LESS_EQUAL,
                    'value' => '20'
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2']
                ]
            ],
            'Filter "less"'              => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type'  => NumberFilterType::TYPE_LESS_THAN,
                    'value' => '20'
                ],
                'expectedResult' => [
                    ['username' => 'u1']
                ]
            ],
            'Filter "is any of"'         => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type'  => NumberFilterType::TYPE_IN,
                    'value' => '10,20'
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2']
                ]
            ],
            'Filter "is not any of"'     => [
                'filterName'     => 'u.loginCount',
                'filterData'     => [
                    'type'  => NumberFilterType::TYPE_NOT_IN,
                    'value' => '10,20'
                ],
                'expectedResult' => [
                    ['username' => 'u3']
                ]
            ]
        ];
    }
}
