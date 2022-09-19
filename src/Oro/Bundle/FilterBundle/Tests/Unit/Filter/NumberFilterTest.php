<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\NumberFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterTypeInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class NumberFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    /** @var NumberFilter */
    protected $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filter = new NumberFilter($this->formFactory, new FilterUtility());
        $this->filter->init('test-filter', [
            FilterUtility::DATA_NAME_KEY => 'field_name'
        ]);
    }

    protected function getFilterDatasource(): OrmFilterDatasourceAdapter
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getExpressionBuilder')
            ->willReturn(new Query\Expr());
        $connection = $this->createMock(Connection::class);
        $em->expects(self::any())
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        return new OrmFilterDatasourceAdapter(new QueryBuilder($em));
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     *
     * @return string
     */
    protected function parseQueryCondition(OrmFilterDatasourceAdapter $ds)
    {
        $qb = $ds->getQueryBuilder();

        $parameters = [];
        /* @var Query\Parameter $param */
        foreach ($qb->getParameters() as $param) {
            $parameters[':' . $param->getName()] = $param->getValue();
        }

        $parts = $qb->getDQLParts();
        if (!$parts['where']) {
            return '';
        }

        $parameterValues = array_map(
            function ($parameterValue) {
                if (is_array($parameterValue)) {
                    $parameterValue = implode(',', $parameterValue);
                }

                return $parameterValue;
            },
            array_values($parameters)
        );

        return str_replace(
            array_keys($parameters),
            $parameterValues,
            (string)$parts['where']
        );
    }

    /**
     * @dataProvider applyProvider
     */
    public function testApply(array $data, array $expected)
    {
        $ds = $this->getFilterDatasource();
        $this->filter->apply($ds, $data);

        $where = $this->parseQueryCondition($ds);
        $this->assertEquals($expected['where'], $where);
    }

    /**
     * @dataProvider applyProviderForDivisor
     */
    public function testApplyForDivisor(array $data, array $expected)
    {
        $this->filter->init('test-filter', [
            FilterUtility::DATA_NAME_KEY => 'field_name',
            FilterUtility::DIVISOR_KEY => 100
        ]);

        $ds = $this->getFilterDatasource();
        $this->filter->apply($ds, $data);

        $where = $this->parseQueryCondition($ds);
        $this->assertEquals($expected['where'], $where);
    }

    /**
     * @dataProvider parseDataProvider
     */
    public function testParseData($data, $expected)
    {
        $this->assertEquals(
            $expected,
            ReflectionUtil::callMethod($this->filter, 'parseData', [$data])
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function applyProvider(): array
    {
        return [
            'GREATER_EQUAL' => [
                'data' => [
                    'type' => NumberFilterType::TYPE_GREATER_EQUAL,
                    'value' => 1
                ],
                'expected' => [
                    'where' => 'field_name >= 1'
                ]
            ],
            'GREATER_THAN' => [
                'data' => [
                    'type' => NumberFilterType::TYPE_GREATER_THAN,
                    'value' => 2
                ],
                'expected' => [
                    'where' => 'field_name > 2'
                ]
            ],
            'EQUAL' => [
                'data' => [
                    'type' => NumberFilterType::TYPE_EQUAL,
                    'value' => 3
                ],
                'expected' => [
                    'where' => 'field_name = 3'
                ]
            ],
            'NOT_EQUAL' => [
                'data' => [
                    'type' => NumberFilterType::TYPE_NOT_EQUAL,
                    'value' => 4
                ],
                'expected' => [
                    'where' => 'field_name <> 4'
                ]
            ],
            'LESS_EQUAL' => [
                'data' => [
                    'type' => NumberFilterType::TYPE_LESS_EQUAL,
                    'value' => 5
                ],
                'expected' => [
                    'where' => 'field_name <= 5'
                ]
            ],
            'LESS_THAN' => [
                'data' => [
                    'type' => NumberFilterType::TYPE_LESS_THAN,
                    'value' => 6
                ],
                'expected' => [
                    'where' => 'field_name < 6'
                ]
            ],
            'EMPTY' => [
                'data' => [
                    'type' => FilterUtility::TYPE_EMPTY,
                    'value' => null
                ],
                'expected' => [
                    'where' => 'field_name IS NULL'
                ]
            ],
            'NOT_EMPTY' => [
                'data' => [
                    'type' => FilterUtility::TYPE_NOT_EMPTY,
                    'value' => null
                ],
                'expected' => [
                    'where' => 'field_name IS NOT NULL'
                ]
            ],
            'IN' => [
                'data' => [
                    'type' => NumberFilterType::TYPE_IN,
                    'value' => '1, 3,4,5'
                ],
                'expected' => [
                    'where' => 'field_name IN(1,3,4,5)'
                ]
            ],
            'NOT IN' => [
                'data' => [
                    'type' => NumberFilterType::TYPE_NOT_IN,
                    'value' => '1, 2, 3, 5'
                ],
                'expected' => [
                    'where' => 'field_name NOT IN(1,2,3,5)'
                ]
            ],
        ];
    }

    public function applyProviderForDivisor()
    {
        return [
            'GREATER_EQUAL' => [
                'data' => [
                    'type' => NumberFilterType::TYPE_GREATER_EQUAL,
                    'value' => 1
                ],
                'expected' => [
                    'where' => 'field_name >= 100'
                ]
            ],
            'GREATER_THAN' => [
                'data' => [
                    'type' => NumberFilterType::TYPE_GREATER_THAN,
                    'value' => 2
                ],
                'expected' => [
                    'where' => 'field_name > 200'
                ]
            ],
            'NO_TYPE' => [
                'data' => [
                    'value' => 3
                ],
                'expected' => [
                    'where' => 'field_name = 300'
                ]
            ],
            'EQUAL' => [
                'data' => [
                    'type' => NumberFilterType::TYPE_EQUAL,
                    'value' => 3
                ],
                'expected' => [
                    'where' => 'field_name = 300'
                ]
            ],
            'NOT_EQUAL' => [
                'data' => [
                    'type' => NumberFilterType::TYPE_NOT_EQUAL,
                    'value' => 4
                ],
                'expected' => [
                    'where' => 'field_name <> 400'
                ]
            ],
            'LESS_EQUAL' => [
                'data' => [
                    'type' => NumberFilterType::TYPE_LESS_EQUAL,
                    'value' => 5
                ],
                'expected' => [
                    'where' => 'field_name <= 500'
                ]
            ],
            'LESS_THAN' => [
                'data' => [
                    'type' => NumberFilterType::TYPE_LESS_THAN,
                    'value' => 6
                ],
                'expected' => [
                    'where' => 'field_name < 600'
                ]
            ],
            'EMPTY' => [
                'data' => [
                    'type' => FilterUtility::TYPE_EMPTY,
                    'value' => null
                ],
                'expected' => [
                    'where' => 'field_name IS NULL'
                ]
            ],
            'NOT_EMPTY' => [
                'data' => [
                    'type' => FilterUtility::TYPE_NOT_EMPTY,
                    'value' => null
                ],
                'expected' => [
                    'where' => 'field_name IS NOT NULL'
                ]
            ],
        ];
    }

    public function parseDataProvider(): array
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
            'invalid data, not numeric value in array' => [
                ['value' => '1,2a,3', 'type' => NumberFilterType::TYPE_IN],
                false
            ],
        ];
    }

    public function testGetMetadata()
    {
        $form = $this->createMock(FormInterface::class);
        $view = $this->createMock(FormView::class);
        $typeView = $this->createMock(FormView::class);
        $typeView->vars['choices'] = [];
        $view->vars['formatter_options'] = ['decimals' => 0, 'grouping' => false];
        $view->vars['array_separator'] = ',';
        $view->vars['array_operators'] = [9, 10];
        $view->vars['data_type'] = 'data_integer';
        $view->vars['limit_decimals'] = true;
        $view->children['type'] = $typeView;

        $this->formFactory->expects($this->any())
            ->method('create')
            ->willReturn($form);
        $form->expects($this->any())
            ->method('createView')
            ->willReturn($view);

        $expected = [
            'name' => 'test-filter',
            'label' => 'Test-filter',
            'choices' => [],
            'lazy' => false,
            'formatterOptions' => [
                'decimals' => 0,
                'grouping' => false
            ],
            'arraySeparator' => ',',
            'arrayOperators' => [9, 10],
            'dataType' => 'data_integer',
            'limitDecimals' => true
        ];
        $this->assertEquals($expected, $this->filter->getMetadata());
    }

    public function testPrepareDataWhenNoValue()
    {
        self::assertSame(
            ['value' => null],
            $this->filter->prepareData([])
        );
    }

    public function testPrepareDataWhenNoValueAndArrayRelatedFilterType()
    {
        self::assertSame(
            ['type' => (string)NumberFilterTypeInterface::TYPE_IN, 'value' => null],
            $this->filter->prepareData(['type' => (string)NumberFilterTypeInterface::TYPE_IN])
        );
    }

    public function testPrepareDataWithNullValue()
    {
        self::assertSame(
            ['value' => null],
            $this->filter->prepareData(['value' => null])
        );
    }

    public function testPrepareDataWithNullValueAndArrayRelatedFilterType()
    {
        self::assertSame(
            ['value' => null, 'type' => (string)NumberFilterTypeInterface::TYPE_IN],
            $this->filter->prepareData(['value' => null, 'type' => (string)NumberFilterTypeInterface::TYPE_IN])
        );
    }

    public function testPrepareDataWithEmptyStringValue()
    {
        self::assertSame(
            ['value' => null],
            $this->filter->prepareData(['value' => ''])
        );
    }

    public function testPrepareDataWithEmptyStringValueAndArrayRelatedFilterType()
    {
        self::assertSame(
            ['value' => null, 'type' => (string)NumberFilterTypeInterface::TYPE_IN],
            $this->filter->prepareData(['value' => '', 'type' => (string)NumberFilterTypeInterface::TYPE_IN])
        );
    }

    public function testPrepareDataWithZeroValueAsString()
    {
        self::assertSame(
            ['value' => 0.0],
            $this->filter->prepareData(['value' => '0'])
        );
    }

    public function testPrepareDataWithZeroValueAsStringAndArrayRelatedFilterType()
    {
        self::assertSame(
            ['value' => [0.0], 'type' => (string)NumberFilterTypeInterface::TYPE_IN],
            $this->filter->prepareData(['value' => '0', 'type' => (string)NumberFilterTypeInterface::TYPE_IN])
        );
    }

    public function testPrepareDataWithZeroValueAsInteger()
    {
        self::assertSame(
            ['value' => 0.0],
            $this->filter->prepareData(['value' => 0])
        );
    }

    public function testPrepareDataWithZeroValueAsIntegerAndArrayRelatedFilterType()
    {
        self::assertSame(
            ['value' => [0.0], 'type' => (string)NumberFilterTypeInterface::TYPE_IN],
            $this->filter->prepareData(['value' => 0, 'type' => (string)NumberFilterTypeInterface::TYPE_IN])
        );
    }

    public function testPrepareDataWithIntegerValue()
    {
        self::assertSame(
            ['value' => 123.0],
            $this->filter->prepareData(['value' => 123])
        );
    }

    public function testPrepareDataWithIntegerValueAndArrayRelatedFilterType()
    {
        self::assertSame(
            ['value' => [123.0], 'type' => (string)NumberFilterTypeInterface::TYPE_IN],
            $this->filter->prepareData(['value' => 123, 'type' => (string)NumberFilterTypeInterface::TYPE_IN])
        );
    }

    public function testPrepareDataWithIntegerValueAsString()
    {
        self::assertSame(
            ['value' => 123.0],
            $this->filter->prepareData(['value' => '123'])
        );
    }

    public function testPrepareDataWithIntegerValueAsStringAndArrayRelatedFilterType()
    {
        self::assertSame(
            ['value' => [123.0, 234.0], 'type' => (string)NumberFilterTypeInterface::TYPE_IN],
            $this->filter->prepareData(['value' => '123 , 234', 'type' => (string)NumberFilterTypeInterface::TYPE_IN])
        );
    }

    public function testPrepareDataWithFloatValue()
    {
        self::assertSame(
            ['value' => 123.1],
            $this->filter->prepareData(['value' => 123.1])
        );
    }

    public function testPrepareDataWithFloatValueAndArrayRelatedFilterType()
    {
        self::assertSame(
            ['value' => [123.1], 'type' => (string)NumberFilterTypeInterface::TYPE_IN],
            $this->filter->prepareData(['value' => 123.1, 'type' => (string)NumberFilterTypeInterface::TYPE_IN])
        );
    }

    public function testPrepareDataWithFloatValueAsString()
    {
        self::assertSame(
            ['value' => 123.1],
            $this->filter->prepareData(['value' => '123.1'])
        );
    }

    public function testPrepareDataWithFloatValueAsStringAndArrayRelatedFilterType()
    {
        self::assertSame(
            ['value' => [123.1], 'type' => (string)NumberFilterTypeInterface::TYPE_IN],
            $this->filter->prepareData(['value' => '123.1', 'type' => (string)NumberFilterTypeInterface::TYPE_IN])
        );
    }

    public function testPrepareDataWithNotNumericStringValue()
    {
        $this->expectException(TransformationFailedException::class);
        $this->filter->prepareData(['value' => 'abc']);
    }

    public function testPrepareDataWithNotNumericStringValueAndArrayRelatedFilterType()
    {
        $this->expectException(TransformationFailedException::class);
        $this->filter->prepareData(['value' => 'abc', 'type' => (string)NumberFilterTypeInterface::TYPE_IN]);
    }

    public function testPrepareDataWithArrayValue()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The value is not valid. Expected a scalar value, "array" given');
        $this->filter->prepareData(['value' => [123]]);
    }

    public function testPrepareDataWithArrayValueAndArrayRelatedFilterType()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The value is not valid. Expected a scalar value, "array" given');
        $this->filter->prepareData(['value' => [123], 'type' => (string)NumberFilterTypeInterface::TYPE_IN]);
    }
}
