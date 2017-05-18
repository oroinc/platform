<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\NumberFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class NumberFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NumberFilter
     */
    protected $filter;

    /**
     * @var string
     */
    protected $filterName = 'filter-name';

    /**
     * @var string
     */
    protected $dataName = 'field-name';

    /**
     * @var string
     */
    protected $parameterName = 'parameter-name';

    /**
     * @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formFactory;

    /**
     * @var FilterUtility|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filterUtility;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->filterUtility = $this->createMock(FilterUtility::class);

        $this->filter = new NumberFilter($this->formFactory, $this->filterUtility);
        $this->filter->init($this->filterName, [
            FilterUtility::DATA_NAME_KEY => $this->dataName,
        ]);
    }

    /**
     * @dataProvider applyProvider
     *
     * @param array $inputData
     * @param array $expectedData
     */
    public function testApply(array $inputData, array $expectedData)
    {
        $ds = $this->prepareDatasource();

        $this->filter->apply($ds, $inputData['data']);

        $where = $this->parseQueryCondition($ds);

        $this->assertEquals($expectedData['where'], $where);
    }

    /**
     * @dataProvider parseDataProvider
     *
     * @param mixed $inputData
     * @param mixed $expectedData
     */
    public function testParseData($inputData, $expectedData)
    {
        $this->assertEquals($expectedData, $this->filter->parseData($inputData));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function applyProvider()
    {
        return [
            'GREATER_EQUAL' => [
                'input' => [
                    'data' => [
                        'type' => NumberFilterType::TYPE_GREATER_EQUAL,
                        'value' => 1,
                    ],
                ],
                'expected' => [
                    'where' => 'field-name >= 1',
                ],
            ],
            'GREATER_THAN' => [
                'input' => [
                    'data' => [
                        'type' => NumberFilterType::TYPE_GREATER_THAN,
                        'value' => 2,
                    ],
                ],
                'expected' => [
                    'where' => 'field-name > 2',
                ],
            ],
            'EQUAL' => [
                'input' => [
                    'data' => [
                        'type' => NumberFilterType::TYPE_EQUAL,
                        'value' => 3,
                    ],
                ],
                'expected' => [
                    'where' => 'field-name = 3',
                ],
            ],
            'NOT_EQUAL' => [
                'input' => [
                    'data' => [
                        'type' => NumberFilterType::TYPE_NOT_EQUAL,
                        'value' => 4,
                    ],
                ],
                'expected' => [
                    'where' => 'field-name <> 4',
                ],
            ],
            'LESS_EQUAL' => [
                'input' => [
                    'data' => [
                        'type' => NumberFilterType::TYPE_LESS_EQUAL,
                        'value' => 5,
                    ],
                ],
                'expected' => [
                    'where' => 'field-name <= 5',
                ],
            ],
            'LESS_THAN' => [
                'input' => [
                    'data' => [
                        'type' => NumberFilterType::TYPE_LESS_THAN,
                        'value' => 6,
                    ],
                ],
                'expected' => [
                    'where' => 'field-name < 6',
                ],
            ],
            'EMPTY' => [
                'input' => [
                    'data' => [
                        'type' => FilterUtility::TYPE_EMPTY,
                        'value' => null,
                    ],
                ],
                'expected' => [
                    'where' => 'field-name IS NULL',
                ],
            ],
            'NOT_EMPTY' => [
                'input' => [
                    'data' => [
                        'type' => FilterUtility::TYPE_NOT_EMPTY,
                        'value' => null,
                    ],
                ],
                'expected' => [
                    'where' => 'field-name IS NOT NULL',
                ],
            ],
            'IN' => [
                'input' => [
                    'data' => [
                        'type' => NumberFilterType::TYPE_IN,
                        'value' => '1, 3,a,5',
                    ],
                ],
                'expected' => [
                    'where' => 'field-name IN(1,3,5)',
                ],
            ],
            'NOT IN' => [
                'input' => [
                    'data' => [
                        'type' => NumberFilterType::TYPE_NOT_IN,
                        'value' => '1, 6bc, 3, 5',
                    ],
                ],
                'expected' => [
                    'where' => 'field-name NOT IN(1,3,5)',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function parseDataProvider()
    {
        return [
            'invalid data, no array' => [
                false,
                false
            ],
            'invalid data, no value' => [
                [],
                false
            ],
            'invalid data, not numeric value' => [
                ['value' => 'value', 'type' => NumberFilterType::TYPE_EQUAL],
                false
            ],
            'valid data, type is TYPE_EMPTY' => [
                ['value' => null, 'type' => FilterUtility::TYPE_EMPTY],
                ['value' => null, 'type' => FilterUtility::TYPE_EMPTY],
            ],
            'valid data, type is TYPE_NOT_EMPTY' => [
                ['value' => null, 'type' => FilterUtility::TYPE_NOT_EMPTY],
                ['value' => null, 'type' => FilterUtility::TYPE_NOT_EMPTY],
            ],
        ];
    }

    public function testGetMetadata()
    {
        $form = $this->createMock(FormInterface::class);
        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->createMock(FormView::class);
        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $typeView */
        $typeView = $this->createMock(FormView::class);
        $typeView->vars['choices'] = [];
        $view->vars['formatter_options'] = ['decimals' => 0, 'grouping' => false];
        $view->vars['array_separator'] = ',';
        $view->vars['array_operators'] = [9, 10];
        $view->vars['data_type'] = 'data_integer';
        $view->children['type'] = $typeView;

        $this->formFactory->expects($this->any())
            ->method('create')
            ->willReturn($form);
        $form->expects($this->any())
            ->method('createView')
            ->willReturn($view);
        $this->filterUtility->expects($this->any())
            ->method('getExcludeParams')
            ->willReturn([]);

        $expected = [
            'name' => 'filter-name',
            'label' => 'Filter-name',
            'choices' => [],
            'data_name' => 'field-name',
            'options' => [],
            'lazy' => false,
            'formatterOptions' => [
                'decimals' => 0,
                'grouping' => false
            ],
            'arraySeparator' => ',',
            'arrayOperators' => [9, 10],
            'dataType' => 'data_integer'
        ];
        $this->assertEquals($expected, $this->filter->getMetadata());
    }

    /**
     * @return OrmFilterDatasourceAdapter
     */
    protected function prepareDatasource()
    {
        /* @var $em EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $em = $this->createMock('Doctrine\ORM\EntityManagerInterface');
        $em->expects($this->any())
            ->method('getExpressionBuilder')
            ->willReturn(new Query\Expr());

        return new OrmFilterDatasourceAdapter(new QueryBuilder($em));
    }


    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @return string
     */
    protected function parseQueryCondition(OrmFilterDatasourceAdapter $ds)
    {
        $qb = $ds->getQueryBuilder();

        $parameters = array();
        foreach ($qb->getParameters() as $param) {
            /* @var $param Query\Parameter */
            $parameters[':' . $param->getName()] = $param->getValue();
        }

        $parts = $qb->getDQLParts();

        $where = '';

        if ($parts['where']) {
            $parameterValues = array_map(
                function ($parameterValue) {
                    if (is_array($parameterValue)) {
                        $parameterValue = implode(',', $parameterValue);
                    }

                    return $parameterValue;
                },
                array_values($parameters)
            );

            $where = str_replace(
                array_keys($parameters),
                $parameterValues,
                (string)$parts['where']
            );
        }

        return $where;
    }
}
