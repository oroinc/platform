<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\DictionaryFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadUserWithBU;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class DictionaryFilterTest extends WebTestCase
{
    /** @var DictionaryFilter */
    protected $filter;

    public function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadUserWithBU::class]);
        $this->filter = $this->getContainer()->get('oro_filter.dictionary_filter');
    }

    /**
     * @dataProvider filterProvider
     *
     * @param string   $dataName
     * @param callable $filterFormData
     * @param array    $expectedResult
     */
    public function testFilter($dataName, callable $filterFormData, array $expectedResult)
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->select('u.username')
            ->orderBy('u.username')
            ->leftJoin('u.businessUnits', 'businessUnits')
            ->andWhere(
                $qb->expr()->in(
                    'u.username',
                    ['u1', 'u2', 'u3']
                )
            );

        $ds = new OrmFilterDatasourceAdapter($qb);

        $filterForm = $this->filter->getForm();
        $filterForm->submit($filterFormData());

        $this->assertTrue($filterForm->isValid());

        $this->filter->init($dataName, ['class' => BusinessUnit::class, 'data_name' => $dataName]);
        $this->filter->apply($ds, $filterForm->getData());

        $result = $ds->getQueryBuilder()->getQuery()->getResult();

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function filterProvider()
    {
        return [
            'Filter "is any of"' => [
                'dataName' => 'businessUnits.id',
                'filterFormData' => $this->getFilterFormDataCallback(DictionaryFilterType::TYPE_IN),
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                ],
            ],
            'Filter "is not any of"' => [
                'filterName' => 'businessUnits.id',
                'filterFormData' => $this->getFilterFormDataCallback(DictionaryFilterType::TYPE_NOT_IN),
                'expectedResult' => [
                    [
                        'username' => 'u3',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param $type
     *
     * @return \Closure
     */
    private function getFilterFormDataCallback($type)
    {
        return function () use ($type) {
            return [
                'type' => $type,
                'value' => $this->getReference('mainBusinessUnit')->getId(),
            ];
        };
    }

    /**
     * @param string $alias
     * @return QueryBuilder
     */
    private function createQueryBuilder($alias)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $objectManager = $doctrine->getManagerForClass(User::class);
        $repository = $objectManager->getRepository(User::class);

        return $repository->createQueryBuilder($alias);
    }
}
