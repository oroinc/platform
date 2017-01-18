<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\FilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;

/**
 * @dbIsolationPerTest
 */
class StringFilterTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient();
    }

    /**
     * @dataProvider filterProvider
     *
     * @param $filterName
     * @param $filterFormData
     * @param $expectedResult
     */
    public function testFilter($filterName, $filterFormData, $expectedResult)
    {
        $registry = $this->getContainer()->get('doctrine');
        $filterDatasourceAdapter = new FilterDatasourceAdapter($registry);
        $stringFilter = $this->getContainer()->get('oro_filter.string_filter');

        $stringFilter->init($filterName, ['enabled' => true, 'type' => 'string', 'data_name' => $filterName]);
        $filterForm = $stringFilter->getForm();
        $filterForm->submit($filterFormData);
        $this->assertTrue($filterForm->isValid(), 'Form shoul\'d be valid !');

        $stringFilter->apply($filterDatasourceAdapter, $filterForm->getData());
        $queryResult = $filterDatasourceAdapter->getQueryBuilder()->getQuery()->getResult();
        $this->assertSame($expectedResult, $queryResult);
    }

    public function filterProvider()
    {
        return [
            'Filter "not empty"' => [
                'filterName' => 'u.username',
                'filterFormData' => [
                    'type' => FilterUtility::TYPE_NOT_EMPTY,
                    'value' => FilterUtility::TYPE_NOT_EMPTY,
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ],
            ],
            'Filter "empty"' => [
                'filterName' => 'u.username',
                'filterFormData' => [
                    'type' => FilterUtility::TYPE_EMPTY,
                    'value' => FilterUtility::TYPE_EMPTY,
                ],
                'expectedResult' => [ ],
            ],
            'Filter "equal"' => [
                'filterName' => 'u.username',
                'filterFormData' => [
                    'type' => TextFilterType::TYPE_EQUAL,
                    'value' => 'u1',
                ],
                'expectedResult' => [
                    ['username' => 'u1']
                ],
            ],
            'Filter "contains"' => [
                'filterName' => 'u.username',
                'filterFormData' => [
                    'type' => TextFilterType::TYPE_CONTAINS,
                    'value' => 'u',
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ],
            ],
            'Filter "does not contain"' => [
                'filterName' => 'u.username',
                'filterFormData' => [
                    'type' => TextFilterType::TYPE_NOT_CONTAINS,
                    'value' => 'u',
                ],
                'expectedResult' => [],
            ],
            'Filter "starts with"' => [
                'filterName' => 'u.username',
                'filterFormData' => [
                    'type' => TextFilterType::TYPE_STARTS_WITH,
                    'value' => 'u',
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ],
            ],
            'Filter "ends with"' => [
                'filterName' => 'u.username',
                'filterFormData' => [
                    'type' => TextFilterType::TYPE_ENDS_WITH,
                    'value' => '3',
                ],
                'expectedResult' => [
                    ['username' => 'u3']
                ],
            ]
        ];
    }
}
