<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\DictionaryFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadUserWithBUAndOrganization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class DictionaryFilterTest extends WebTestCase
{
    private DictionaryFilter $filter;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadUserWithBUAndOrganization::class]);
        $this->filter = self::getContainer()->get('oro_filter.dictionary_filter');
    }

    private function createQueryBuilder(string $alias): QueryBuilder
    {
        $doctrine = self::getContainer()->get('doctrine');

        return $doctrine->getRepository(User::class)->createQueryBuilder($alias);
    }

    private function getFilterDataCallback(string|int $type, ?string $reference): callable
    {
        return function () use ($type, $reference) {
            return [
                'type'  => $type,
                'value' => $reference ? $this->getReference($reference)->getId() : null
            ];
        };
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilterWithForm(string $join, string $dataName, callable $filterData, array $expectedResult)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username')
            ->leftJoin('u.' . $join, $join)
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        $this->filter->init($dataName, ['data_name' => $dataName]);

        $filterForm = $this->filter->getForm();
        $filterForm->submit($filterData());
        self::assertTrue($filterForm->isValid());
        self::assertTrue($filterForm->isSynchronized());

        $ds = new OrmFilterDatasourceAdapter($qb);
        $this->filter->apply($ds, $filterForm->getData());

        $result = $ds->getQueryBuilder()->getQuery()->getResult();
        self::assertSame($expectedResult, $result);
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilterWithoutForm(string $join, string $dataName, callable $filterData, array $expectedResult)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username')
            ->leftJoin('u.' . $join, $join)
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        $this->filter->init($dataName, ['data_name' => $dataName]);

        $data = $this->filter->prepareData($filterData());

        $ds = new OrmFilterDatasourceAdapter($qb);
        $this->filter->apply($ds, $data);

        $result = $ds->getQueryBuilder()->getQuery()->getResult();
        self::assertSame($expectedResult, $result);
    }

    public function filterProvider(): array
    {
        return [
            'Filter "is any of" for toOne relation' => [
                'join'           => 'organization',
                'dataName'       => 'organization.id',
                'filterData'     => $this->getFilterDataCallback(DictionaryFilterType::TYPE_IN, 'mainOrganization'),
                'expectedResult' => [
                    ['username' => 'u3']
                ]
            ],
            'Filter "is any of"'                    => [
                'join'           => 'businessUnits',
                'dataName'       => 'businessUnits.id',
                'filterData'     => $this->getFilterDataCallback(DictionaryFilterType::TYPE_IN, 'mainBusinessUnit'),
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2']
                ]
            ],
            'Filter "is not any of"'                => [
                'join'           => 'businessUnits',
                'dataName'       => 'businessUnits.id',
                'filterData'     => $this->getFilterDataCallback(DictionaryFilterType::TYPE_NOT_IN, 'mainBusinessUnit'),
                'expectedResult' => [
                    ['username' => 'u3']
                ]
            ],
            'Filter "is empty"'                     => [
                'join'           => 'businessUnits',
                'dataName'       => 'businessUnits.id',
                'filterData'     => $this->getFilterDataCallback(FilterUtility::TYPE_EMPTY, null),
                'expectedResult' => [
                    ['username' => 'u3']
                ]
            ],
            'Filter "is not empty"'                 => [
                'join'           => 'businessUnits',
                'dataName'       => 'businessUnits.id',
                'filterData'     => $this->getFilterDataCallback(FilterUtility::TYPE_NOT_EMPTY, null),
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2']
                ]
            ]
        ];
    }
}
