<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterOperatorException;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Model\NormalizedDateTime;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ComparisonFilterTest extends TestCase
{
    private function getDateTime(string $datetime): \DateTime
    {
        return new \DateTime($datetime, new \DateTimeZone('UTC'));
    }

    private function getNormalizedDateTime(
        string $datetime,
        int $precision = NormalizedDateTime::PRECISION_SECOND
    ): NormalizedDateTime {
        $result = new NormalizedDateTime($datetime, new \DateTimeZone('UTC'));
        $result->setPrecision($precision);

        return $result;
    }

    public function testSetAndGetField(): void
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        self::assertNull($comparisonFilter->getField());

        $fieldName = 'test';
        $comparisonFilter->setField($fieldName);
        self::assertSame($fieldName, $comparisonFilter->getField());
    }

    public function testSetAndGetSupportedOperators(): void
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        self::assertEquals([FilterOperator::EQ], $comparisonFilter->getSupportedOperators());

        $supportedOperators = [FilterOperator::EQ, FilterOperator::NEQ];
        $comparisonFilter->setSupportedOperators($supportedOperators);
        self::assertEquals($supportedOperators, $comparisonFilter->getSupportedOperators());
    }

    public function testEmptyFieldName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The field must not be empty.');

        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->apply(new Criteria(), new FilterValue('path', 'value', FilterOperator::EQ));
    }

    public function testNullValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must not be NULL. Field: "fieldName".');

        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setField('fieldName');
        $comparisonFilter->apply(new Criteria(), new FilterValue('path', null, FilterOperator::EQ));
    }

    public function testUnsupportedOperator(): void
    {
        $this->expectException(InvalidFilterOperatorException::class);
        $this->expectExceptionMessage('The operator "neq" is not supported.');

        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setField('fieldName');
        $comparisonFilter->apply(new Criteria(), new FilterValue('path', 'value', FilterOperator::NEQ));
    }

    public function testFilterWhenOperatorsAreNotSpecified(): void
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setField('fieldName');

        self::assertEquals([FilterOperator::EQ], $comparisonFilter->getSupportedOperators());

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, new FilterValue('path', 'value', FilterOperator::EQ));

        self::assertEquals(
            new Comparison('fieldName', Comparison::EQ, new Value('value')),
            $criteria->getWhereExpression()
        );
    }

    public function testFilterWhenOnlyEqualOperatorIsSpecified(): void
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setSupportedOperators([FilterOperator::EQ]);
        $comparisonFilter->setField('fieldName');

        self::assertEquals([FilterOperator::EQ], $comparisonFilter->getSupportedOperators());

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, new FilterValue('path', 'value', FilterOperator::EQ));

        self::assertEquals(
            new Comparison('fieldName', Comparison::EQ, new Value('value')),
            $criteria->getWhereExpression()
        );
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter(
        string $fieldName,
        bool $isArrayAllowed,
        bool $isRangeAllowed,
        ?FilterValue $filterValue,
        ?Expression $expectation
    ): void {
        $supportedOperators = [
            FilterOperator::EQ,
            FilterOperator::NEQ,
            FilterOperator::LT,
            FilterOperator::LTE,
            FilterOperator::GT,
            FilterOperator::GTE,
            FilterOperator::EXISTS,
            FilterOperator::NEQ_OR_NULL,
            FilterOperator::CONTAINS,
            FilterOperator::NOT_CONTAINS,
            FilterOperator::STARTS_WITH,
            FilterOperator::NOT_STARTS_WITH,
            FilterOperator::ENDS_WITH,
            FilterOperator::NOT_ENDS_WITH,
            FilterOperator::EMPTY_VALUE
        ];

        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setSupportedOperators($supportedOperators);
        $comparisonFilter->setField($fieldName);

        $comparisonFilter->setArrayAllowed(true); // set to TRUE due parent should allow own check
        $comparisonFilter->setRangeAllowed(true); // set to TRUE due parent should allow own check
        if ($filterValue) {
            self::assertSame($isArrayAllowed, $comparisonFilter->isArrayAllowed($filterValue->getOperator()));
            self::assertSame($isRangeAllowed, $comparisonFilter->isRangeAllowed($filterValue->getOperator()));
        }

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, $filterValue);

        self::assertEquals($expectation, $criteria->getWhereExpression());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function filterDataProvider(): array
    {
        return [
            'empty filter' => [
                'fieldName', //fieldName
                true, // isArrayAllowed
                true, // isRangeAllowed
                null, // filter
                null // expectation
            ],
            'filter with default operator' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value'),
                new Comparison('fieldName', Comparison::EQ, new Value('value'))
            ],
            'EQ filter' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', FilterOperator::EQ),
                new Comparison('fieldName', Comparison::EQ, new Value('value'))
            ],
            'NEQ filter' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', FilterOperator::NEQ),
                new Comparison('fieldName', Comparison::NEQ, new Value('value'))
            ],
            'EQ filter, DateTime' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', $this->getDateTime('2010-01-28'), FilterOperator::EQ),
                new Comparison('fieldName', Comparison::EQ, new Value($this->getDateTime('2010-01-28')))
            ],
            'NEQ filter, DateTime' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', $this->getDateTime('2010-01-28'), FilterOperator::NEQ),
                new Comparison('fieldName', Comparison::NEQ, new Value($this->getDateTime('2010-01-28')))
            ],
            'EQ filter, NormalizedDateTime:second' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', $this->getNormalizedDateTime('2010-01-28T15:10:05'), FilterOperator::EQ),
                new Comparison(
                    'fieldName',
                    Comparison::EQ,
                    new Value($this->getNormalizedDateTime('2010-01-28T15:10:05'))
                )
            ],
            'NEQ filter, NormalizedDateTime:second' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', $this->getNormalizedDateTime('2010-01-28T15:10:05'), FilterOperator::NEQ),
                new Comparison(
                    'fieldName',
                    Comparison::NEQ,
                    new Value($this->getNormalizedDateTime('2010-01-28T15:10:05'))
                )
            ],
            'EQ filter, NormalizedDateTime:minute' => [
                'fieldName',
                true,
                true,
                new FilterValue(
                    'path',
                    $this->getNormalizedDateTime('2010-01-28T15:10', NormalizedDateTime::PRECISION_MINUTE),
                    FilterOperator::EQ
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison(
                            'fieldName',
                            Comparison::GTE,
                            new Value(
                                $this->getNormalizedDateTime('2010-01-28T15:10', NormalizedDateTime::PRECISION_MINUTE)
                            )
                        ),
                        new Comparison(
                            'fieldName',
                            Comparison::LT,
                            new Value($this->getDateTime('2010-01-28T15:11'))
                        )
                    ]
                )
            ],
            'NEQ filter, NormalizedDateTime:minute' => [
                'fieldName',
                true,
                true,
                new FilterValue(
                    'path',
                    $this->getNormalizedDateTime('2010-01-28T15:10', NormalizedDateTime::PRECISION_MINUTE),
                    FilterOperator::NEQ
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison(
                            'fieldName',
                            Comparison::LT,
                            new Value(
                                $this->getNormalizedDateTime('2010-01-28T15:10', NormalizedDateTime::PRECISION_MINUTE)
                            )
                        ),
                        new Comparison(
                            'fieldName',
                            Comparison::GTE,
                            new Value($this->getDateTime('2010-01-28T15:11'))
                        )
                    ]
                )
            ],
            'EQ filter, NormalizedDateTime:hour' => [
                'fieldName',
                true,
                true,
                new FilterValue(
                    'path',
                    $this->getNormalizedDateTime('2010-01-28T15', NormalizedDateTime::PRECISION_HOUR),
                    FilterOperator::EQ
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison(
                            'fieldName',
                            Comparison::GTE,
                            new Value(
                                $this->getNormalizedDateTime('2010-01-28T15', NormalizedDateTime::PRECISION_HOUR)
                            )
                        ),
                        new Comparison(
                            'fieldName',
                            Comparison::LT,
                            new Value($this->getDateTime('2010-01-28T16'))
                        )
                    ]
                )
            ],
            'NEQ filter, NormalizedDateTime:hour' => [
                'fieldName',
                true,
                true,
                new FilterValue(
                    'path',
                    $this->getNormalizedDateTime('2010-01-28T15', NormalizedDateTime::PRECISION_HOUR),
                    FilterOperator::NEQ
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison(
                            'fieldName',
                            Comparison::LT,
                            new Value(
                                $this->getNormalizedDateTime('2010-01-28T15', NormalizedDateTime::PRECISION_HOUR)
                            )
                        ),
                        new Comparison(
                            'fieldName',
                            Comparison::GTE,
                            new Value($this->getDateTime('2010-01-28T16'))
                        )
                    ]
                )
            ],
            'EQ filter, NormalizedDateTime:day' => [
                'fieldName',
                true,
                true,
                new FilterValue(
                    'path',
                    $this->getNormalizedDateTime('2010-01-28', NormalizedDateTime::PRECISION_DAY),
                    FilterOperator::EQ
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison(
                            'fieldName',
                            Comparison::GTE,
                            new Value($this->getNormalizedDateTime('2010-01-28', NormalizedDateTime::PRECISION_DAY))
                        ),
                        new Comparison(
                            'fieldName',
                            Comparison::LT,
                            new Value($this->getDateTime('2010-01-29'))
                        )
                    ]
                )
            ],
            'NEQ filter, NormalizedDateTime:day' => [
                'fieldName',
                true,
                true,
                new FilterValue(
                    'path',
                    $this->getNormalizedDateTime('2010-01-28', NormalizedDateTime::PRECISION_DAY),
                    FilterOperator::NEQ
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison(
                            'fieldName',
                            Comparison::LT,
                            new Value($this->getNormalizedDateTime('2010-01-28', NormalizedDateTime::PRECISION_DAY))
                        ),
                        new Comparison(
                            'fieldName',
                            Comparison::GTE,
                            new Value($this->getDateTime('2010-01-29'))
                        )
                    ]
                )
            ],
            'EQ filter, NormalizedDateTime:month' => [
                'fieldName',
                true,
                true,
                new FilterValue(
                    'path',
                    $this->getNormalizedDateTime('2010-12-01', NormalizedDateTime::PRECISION_MONTH),
                    FilterOperator::EQ
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison(
                            'fieldName',
                            Comparison::GTE,
                            new Value($this->getNormalizedDateTime('2010-12-01', NormalizedDateTime::PRECISION_MONTH))
                        ),
                        new Comparison(
                            'fieldName',
                            Comparison::LT,
                            new Value($this->getDateTime('2011-01-01'))
                        )
                    ]
                )
            ],
            'NEQ filter, NormalizedDateTime:month' => [
                'fieldName',
                true,
                true,
                new FilterValue(
                    'path',
                    $this->getNormalizedDateTime('2010-12-01', NormalizedDateTime::PRECISION_MONTH),
                    FilterOperator::NEQ
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison(
                            'fieldName',
                            Comparison::LT,
                            new Value($this->getNormalizedDateTime('2010-12-01', NormalizedDateTime::PRECISION_MONTH))
                        ),
                        new Comparison(
                            'fieldName',
                            Comparison::GTE,
                            new Value($this->getDateTime('2011-01-01'))
                        )
                    ]
                )
            ],
            'EQ filter, NormalizedDateTime:year' => [
                'fieldName',
                true,
                true,
                new FilterValue(
                    'path',
                    $this->getNormalizedDateTime('2010-01-01', NormalizedDateTime::PRECISION_YEAR),
                    FilterOperator::EQ
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison(
                            'fieldName',
                            Comparison::GTE,
                            new Value($this->getNormalizedDateTime('2010-01-01', NormalizedDateTime::PRECISION_YEAR))
                        ),
                        new Comparison(
                            'fieldName',
                            Comparison::LT,
                            new Value($this->getDateTime('2011-01-01'))
                        )
                    ]
                )
            ],
            'NEQ filter, NormalizedDateTime:year' => [
                'fieldName',
                true,
                true,
                new FilterValue(
                    'path',
                    $this->getNormalizedDateTime('2010-01-01', NormalizedDateTime::PRECISION_YEAR),
                    FilterOperator::NEQ
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison(
                            'fieldName',
                            Comparison::LT,
                            new Value($this->getNormalizedDateTime('2010-01-01', NormalizedDateTime::PRECISION_YEAR))
                        ),
                        new Comparison(
                            'fieldName',
                            Comparison::GTE,
                            new Value($this->getDateTime('2011-01-01'))
                        )
                    ]
                )
            ],
            'LT filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', FilterOperator::LT),
                new Comparison('fieldName', Comparison::LT, new Value('value'))
            ],
            'LTE filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', FilterOperator::LTE),
                new Comparison('fieldName', Comparison::LTE, new Value('value'))
            ],
            'GT filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', FilterOperator::GT),
                new Comparison('fieldName', Comparison::GT, new Value('value'))
            ],
            'GTE filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', FilterOperator::GTE),
                new Comparison('fieldName', Comparison::GTE, new Value('value'))
            ],
            'EQ filter for array' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', ['value1', 'value2'], FilterOperator::EQ),
                new Comparison('fieldName', Comparison::IN, new Value(['value1', 'value2']))
            ],
            'NEQ filter for array' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', ['value1', 'value2'], FilterOperator::NEQ),
                new Comparison('fieldName', Comparison::NIN, new Value(['value1', 'value2']))
            ],
            'EQ filter for array, DateTime' => [
                'fieldName',
                true,
                true,
                new FilterValue(
                    'path',
                    [$this->getDateTime('2010-01-28'), $this->getDateTime('2010-10-15')],
                    FilterOperator::EQ
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison(
                            'fieldName',
                            Comparison::EQ,
                            new Value($this->getDateTime('2010-01-28'))
                        ),
                        new Comparison(
                            'fieldName',
                            Comparison::EQ,
                            new Value($this->getDateTime('2010-10-15'))
                        )
                    ]
                )
            ],
            'NEQ filter for array, DateTime' => [
                'fieldName',
                true,
                true,
                new FilterValue(
                    'path',
                    [$this->getDateTime('2010-01-28'), $this->getDateTime('2010-10-15')],
                    FilterOperator::NEQ
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison(
                            'fieldName',
                            Comparison::NEQ,
                            new Value($this->getDateTime('2010-01-28'))
                        ),
                        new Comparison(
                            'fieldName',
                            Comparison::NEQ,
                            new Value($this->getDateTime('2010-10-15'))
                        )
                    ]
                )
            ],
            'EQ filter for array, NormalizedDateTime' => [
                'fieldName',
                true,
                true,
                new FilterValue(
                    'path',
                    [
                        $this->getNormalizedDateTime('2010-01-28', NormalizedDateTime::PRECISION_DAY),
                        $this->getNormalizedDateTime('2010-12-01', NormalizedDateTime::PRECISION_MONTH)
                    ],
                    FilterOperator::EQ
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new CompositeExpression(
                            CompositeExpression::TYPE_AND,
                            [
                                new Comparison(
                                    'fieldName',
                                    Comparison::GTE,
                                    new Value(
                                        $this->getNormalizedDateTime('2010-01-28', NormalizedDateTime::PRECISION_DAY)
                                    )
                                ),
                                new Comparison(
                                    'fieldName',
                                    Comparison::LT,
                                    new Value($this->getDateTime('2010-01-29'))
                                )
                            ]
                        ),
                        new CompositeExpression(
                            CompositeExpression::TYPE_AND,
                            [
                                new Comparison(
                                    'fieldName',
                                    Comparison::GTE,
                                    new Value(
                                        $this->getNormalizedDateTime('2010-12-01', NormalizedDateTime::PRECISION_MONTH)
                                    )
                                ),
                                new Comparison(
                                    'fieldName',
                                    Comparison::LT,
                                    new Value($this->getDateTime('2011-01-01'))
                                )
                            ]
                        )
                    ]
                )
            ],
            'NEQ filter for array, NormalizedDateTime' => [
                'fieldName',
                true,
                true,
                new FilterValue(
                    'path',
                    [
                        $this->getNormalizedDateTime('2010-01-28', NormalizedDateTime::PRECISION_DAY),
                        $this->getNormalizedDateTime('2010-12-01', NormalizedDateTime::PRECISION_MONTH)
                    ],
                    FilterOperator::NEQ
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new CompositeExpression(
                            CompositeExpression::TYPE_OR,
                            [
                                new Comparison(
                                    'fieldName',
                                    Comparison::LT,
                                    new Value(
                                        $this->getNormalizedDateTime('2010-01-28', NormalizedDateTime::PRECISION_DAY)
                                    )
                                ),
                                new Comparison(
                                    'fieldName',
                                    Comparison::GTE,
                                    new Value($this->getDateTime('2010-01-29'))
                                )
                            ]
                        ),
                        new CompositeExpression(
                            CompositeExpression::TYPE_OR,
                            [
                                new Comparison(
                                    'fieldName',
                                    Comparison::LT,
                                    new Value(
                                        $this->getNormalizedDateTime('2010-12-01', NormalizedDateTime::PRECISION_MONTH)
                                    )
                                ),
                                new Comparison(
                                    'fieldName',
                                    Comparison::GTE,
                                    new Value($this->getDateTime('2011-01-01'))
                                )
                            ]
                        )
                    ]
                )
            ],
            'EQ filter for range' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', new Range('value1', 'value2'), FilterOperator::EQ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison('fieldName', Comparison::GTE, new Value('value1')),
                        new Comparison('fieldName', Comparison::LTE, new Value('value2'))
                    ]
                )
            ],
            'NEQ filter for range' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', new Range('value1', 'value2'), FilterOperator::NEQ),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison('fieldName', Comparison::LT, new Value('value1')),
                        new Comparison('fieldName', Comparison::GT, new Value('value2'))
                    ]
                )
            ],
            'EXISTS filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', true, FilterOperator::EXISTS),
                new Comparison('fieldName', 'EXISTS', new Value(true))
            ],
            'NOT EXISTS filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', false, FilterOperator::EXISTS),
                new Comparison('fieldName', 'EXISTS', new Value(false))
            ],
            'NEQ_OR_NULL filter' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', FilterOperator::NEQ_OR_NULL),
                new Comparison('fieldName', 'NEQ_OR_NULL', new Value('value'))
            ],
            'CONTAINS filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', FilterOperator::CONTAINS),
                new Comparison('fieldName', 'CONTAINS', new Value('value'))
            ],
            'NOT_CONTAINS filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', FilterOperator::NOT_CONTAINS),
                new Comparison('fieldName', 'NOT_CONTAINS', new Value('value'))
            ],
            'STARTS_WITH filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', FilterOperator::STARTS_WITH),
                new Comparison('fieldName', 'STARTS_WITH', new Value('value'))
            ],
            'NOT_STARTS_WITH filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', FilterOperator::NOT_STARTS_WITH),
                new Comparison('fieldName', 'NOT_STARTS_WITH', new Value('value'))
            ],
            'ENDS_WITH filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', FilterOperator::ENDS_WITH),
                new Comparison('fieldName', 'ENDS_WITH', new Value('value'))
            ],
            'NOT_ENDS_WITH filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', FilterOperator::NOT_ENDS_WITH),
                new Comparison('fieldName', 'NOT_ENDS_WITH', new Value('value'))
            ],
            'EMPTY_VALUE filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', true, FilterOperator::EMPTY_VALUE),
                new Comparison('fieldName', 'EMPTY_VALUE/:integer', new Value(true))
            ],
            'NOT EMPTY_VALUE filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', false, FilterOperator::EMPTY_VALUE),
                new Comparison('fieldName', 'EMPTY_VALUE/:integer', new Value(false))
            ]
        ];
    }

    public function testFilterWithDefaultValue(): void
    {
        $comparisonFilter = new ComparisonFilter(DataType::STRING);
        $comparisonFilter->setField('fieldName');
        $comparisonFilter->setDefaultValue('default');

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, null);

        self::assertEquals(
            new Comparison('fieldName', Comparison::EQ, new Value('default')),
            $criteria->getWhereExpression()
        );
    }

    public function testFilterWithCallbackDefaultValue(): void
    {
        $comparisonFilter = new ComparisonFilter(DataType::STRING);
        $comparisonFilter->setField('fieldName');
        $comparisonFilter->setDefaultValue(function () {
            return 'default';
        });

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, null);

        self::assertEquals(
            new Comparison('fieldName', Comparison::EQ, new Value('default')),
            $criteria->getWhereExpression()
        );
    }

    public function testFilterWithDefaultValueEqualsToNull(): void
    {
        $comparisonFilter = new ComparisonFilter(DataType::STRING);
        $comparisonFilter->setField('fieldName');
        $comparisonFilter->setDefaultValue(null);

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, null);

        self::assertNull($criteria->getWhereExpression());
    }

    public function testFilterWithCallbackDefaultValueEqualsToNull(): void
    {
        $comparisonFilter = new ComparisonFilter(DataType::STRING);
        $comparisonFilter->setField('fieldName');
        $comparisonFilter->setDefaultValue(function () {
            return null;
        });

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, null);

        self::assertNull($criteria->getWhereExpression());
    }

    /**
     * @dataProvider caseInsensitiveFilterDataProvider
     */
    public function testCaseInsensitiveFilter(
        string $fieldName,
        ?FilterValue $filterValue,
        ?Expression $expectation
    ): void {
        $supportedOperators = [
            FilterOperator::EQ,
            FilterOperator::NEQ,
            FilterOperator::NEQ_OR_NULL,
            FilterOperator::CONTAINS,
            FilterOperator::NOT_CONTAINS,
            FilterOperator::STARTS_WITH,
            FilterOperator::NOT_STARTS_WITH,
            FilterOperator::ENDS_WITH,
            FilterOperator::NOT_ENDS_WITH
        ];

        $comparisonFilter = new ComparisonFilter(DataType::STRING);
        $comparisonFilter->setSupportedOperators($supportedOperators);
        $comparisonFilter->setField($fieldName);
        $comparisonFilter->setCaseInsensitive(true);

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, $filterValue);

        self::assertEquals($expectation, $criteria->getWhereExpression());
    }

    public function caseInsensitiveFilterDataProvider(): array
    {
        return [
            'EQ filter' => [
                'fieldName',
                new FilterValue('path', 'Value', FilterOperator::EQ),
                new Comparison('fieldName', Comparison::EQ . '/i', new Value('Value'))
            ],
            'NEQ filter' => [
                'fieldName',
                new FilterValue('path', 'Value', FilterOperator::NEQ),
                new Comparison('fieldName', Comparison::NEQ . '/i', new Value('Value'))
            ],
            'EQ filter for array' => [
                'fieldName',
                new FilterValue('path', ['Value1', 'Value2'], FilterOperator::EQ),
                new Comparison('fieldName', Comparison::IN . '/i', new Value(['Value1', 'Value2']))
            ],
            'NEQ filter for array' => [
                'fieldName',
                new FilterValue('path', ['Value1', 'Value2'], FilterOperator::NEQ),
                new Comparison('fieldName', Comparison::NIN . '/i', new Value(['Value1', 'Value2']))
            ],
            'NEQ_OR_NULL filter' => [
                'fieldName',
                new FilterValue('path', 'Value', FilterOperator::NEQ_OR_NULL),
                new Comparison('fieldName', 'NEQ_OR_NULL/i', new Value('Value'))
            ],
            'CONTAINS filter' => [
                'fieldName',
                new FilterValue('path', 'Value', FilterOperator::CONTAINS),
                new Comparison('fieldName', 'CONTAINS/i', new Value('Value'))
            ],
            'NOT_CONTAINS filter' => [
                'fieldName',
                new FilterValue('path', 'Value', FilterOperator::NOT_CONTAINS),
                new Comparison('fieldName', 'NOT_CONTAINS/i', new Value('Value'))
            ],
            'STARTS_WITH filter' => [
                'fieldName',
                new FilterValue('path', 'Value', FilterOperator::STARTS_WITH),
                new Comparison('fieldName', 'STARTS_WITH/i', new Value('Value'))
            ],
            'NOT_STARTS_WITH filter' => [
                'fieldName',
                new FilterValue('path', 'Value', FilterOperator::NOT_STARTS_WITH),
                new Comparison('fieldName', 'NOT_STARTS_WITH/i', new Value('Value'))
            ],
            'ENDS_WITH filter' => [
                'fieldName',
                new FilterValue('path', 'Value', FilterOperator::ENDS_WITH),
                new Comparison('fieldName', 'ENDS_WITH/i', new Value('Value'))
            ],
            'NOT_ENDS_WITH filter' => [
                'fieldName',
                new FilterValue('path', 'Value', FilterOperator::NOT_ENDS_WITH),
                new Comparison('fieldName', 'NOT_ENDS_WITH/i', new Value('Value'))
            ]
        ];
    }

    /**
     * @dataProvider collectionFilterDataProvider
     */
    public function testCollectionFilter(
        string $fieldName,
        bool $isArrayAllowed,
        bool $isRangeAllowed,
        ?FilterValue $filterValue,
        ?Expression $expectation
    ): void {
        $supportedOperators = [
            FilterOperator::EQ,
            FilterOperator::NEQ,
            FilterOperator::EXISTS,
            FilterOperator::NEQ_OR_NULL,
            FilterOperator::CONTAINS,
            FilterOperator::NOT_CONTAINS
        ];

        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setSupportedOperators($supportedOperators);
        $comparisonFilter->setField($fieldName);
        $comparisonFilter->setCollection(true);

        $comparisonFilter->setArrayAllowed(true); // set to TRUE due parent should allow own check
        $comparisonFilter->setRangeAllowed(true); // set to TRUE due parent should allow own check
        if ($filterValue) {
            self::assertSame($isArrayAllowed, $comparisonFilter->isArrayAllowed($filterValue->getOperator()));
            self::assertSame($isRangeAllowed, $comparisonFilter->isRangeAllowed($filterValue->getOperator()));
        }

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, $filterValue);

        self::assertEquals($expectation, $criteria->getWhereExpression());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function collectionFilterDataProvider(): array
    {
        return [
            'empty filter' => [
                'fieldName', //fieldName
                true, // isArrayAllowed
                true, // isRangeAllowed
                null, // filter
                null // expectation
            ],
            'filter with default operator' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value'),
                new Comparison('fieldName', Comparison::MEMBER_OF, new Value('value'))
            ],
            'EQ filter' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', FilterOperator::EQ),
                new Comparison('fieldName', Comparison::MEMBER_OF, new Value('value'))
            ],
            'NEQ filter' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', FilterOperator::NEQ),
                new CompositeExpression(
                    'NOT',
                    [
                        new Comparison('fieldName', Comparison::MEMBER_OF, new Value('value'))
                    ]
                )
            ],
            'EQ filter for array' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', ['value1', 'value2'], FilterOperator::EQ),
                new Comparison('fieldName', Comparison::MEMBER_OF, new Value(['value1', 'value2']))
            ],
            'NEQ filter for array' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', ['value1', 'value2'], FilterOperator::NEQ),
                new CompositeExpression(
                    'NOT',
                    [
                        new Comparison('fieldName', Comparison::MEMBER_OF, new Value(['value1', 'value2']))
                    ]
                )
            ],
            'EQ filter for range' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', new Range('value1', 'value2'), FilterOperator::EQ),
                new Comparison('fieldName', Comparison::MEMBER_OF, new Value(new Range('value1', 'value2')))
            ],
            'NEQ filter for range' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', new Range('value1', 'value2'), FilterOperator::NEQ),
                new CompositeExpression(
                    'NOT',
                    [
                        new Comparison('fieldName', Comparison::MEMBER_OF, new Value(new Range('value1', 'value2')))
                    ]
                )
            ],
            'EXISTS filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', true, FilterOperator::EXISTS),
                new Comparison('fieldName', 'EMPTY', new Value(false))
            ],
            'NOT EXISTS filter' => [
                'fieldName',
                false,
                false,
                new FilterValue('path', false, FilterOperator::EXISTS),
                new Comparison('fieldName', 'EMPTY', new Value(true))
            ],
            'NEQ_OR_NULL filter' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', FilterOperator::NEQ_OR_NULL),
                new Comparison('fieldName', 'NEQ_OR_EMPTY', new Value('value'))
            ],
            'CONTAINS filter' => [
                'fieldName',
                true,
                false,
                new FilterValue('path', 'value', FilterOperator::CONTAINS),
                new Comparison('fieldName', 'ALL_MEMBER_OF', new Value('value'))
            ],
            'NOT_CONTAINS filter' => [
                'fieldName',
                true,
                false,
                new FilterValue('path', 'value', FilterOperator::NOT_CONTAINS),
                new Comparison('fieldName', 'ALL_NOT_MEMBER_OF', new Value('value'))
            ]
        ];
    }

    public function testFilterForComputedField(): void
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setField(ConfigUtil::IGNORE_PROPERTY_PATH);

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, new FilterValue('path', 'value'));

        self::assertNull($criteria->getWhereExpression());
    }
}
