<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestAllDataTypes;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FiltersByFieldsTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/supported_data_types.yml'
        ]);
    }

    /**
     * @param array  $expectedRows
     * @param string $entityType
     */
    private function prepareExpectedRows(array &$expectedRows, $entityType)
    {
        foreach ($expectedRows as &$row) {
            $row['type'] = $entityType;
        }
    }

    /**
     * @return bool
     */
    private function isPostgreSql()
    {
        return $this->getEntityManager()->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform;
    }

    /**
     * @dataProvider equalFilterDataProvider
     */
    public function testEqualFilter(array $filter, array $expectedRows)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @dataProvider equalFilterDataProvider
     */
    public function testEqualFilterAlternativeSyntax(array $filter, array $expectedRows)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $key = key($filter);
        $filter[$key] = ['eq' => $filter[$key]];

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function equalFilterDataProvider()
    {
        $expectedRows = [['id' => '<toString(@TestItem2->id)>']];

        return [
            'by string field'      => [
                ['fieldString' => 'Test String 2 Value'],
                $expectedRows
            ],
            'by integer field'     => [
                ['fieldInt' => '2'],
                $expectedRows
            ],
            'by smallint field'    => [
                ['fieldSmallInt' => '2'],
                $expectedRows
            ],
            'by bigint field'      => [
                ['fieldBigInt' => '234567890123456'],
                $expectedRows
            ],
            'by boolean field'     => [
                ['fieldBoolean' => 'true'],
                $expectedRows
            ],
            'by decimal field'     => [
                ['fieldDecimal' => '2.345678'],
                $expectedRows
            ],
            'by float field'       => [
                ['fieldFloat' => '2.2'],
                $expectedRows
            ],
            'by datetime field'    => [
                ['fieldDateTime' => '2010-11-01T10:12:13+00:00'],
                $expectedRows
            ],
            'by date field'        => [
                ['fieldDate' => '2010-11-01'],
                $expectedRows
            ],
            'by time field'        => [
                ['fieldTime' => '10:12:13'],
                $expectedRows
            ],
            'by guid field'        => [
                ['fieldGuid' => '12c9746c-f44d-4a84-a72c-bdf750c70568'],
                $expectedRows
            ],
            'by percent field'     => [
                ['fieldPercent' => '0.2'],
                $expectedRows
            ],
            'by money field'       => [
                ['fieldMoney' => '2.3456'],
                $expectedRows
            ],
            'by duration field'    => [
                ['fieldDuration' => '22'],
                $expectedRows
            ],
            'by money_value field' => [
                ['fieldMoneyValue' => '2.3456'],
                $expectedRows
            ],
            'by currency field'    => [
                ['fieldCurrency' => 'UAH'],
                $expectedRows
            ],
        ];
    }

    /**
     * @dataProvider notEqualFilterDataProvider
     */
    public function testNotEqualFilter(array $filter, array $expectedRows)
    {
        $key = key($filter);
        $filter = [sprintf('filter[%s]!', $key) => $filter[$key]];

        // this is a workaround for a known PDO driver issue not saving null to nullable boolean field
        // for PostgreSQL, see https://github.com/doctrine/dbal/issues/2580 for details
        if ('fieldBoolean' === $key && $this->isPostgreSql()) {
            $expectedRows = array_merge($expectedRows, [['id' => '<toString(@NullItem->id)>']]);
        }

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @dataProvider notEqualFilterDataProvider
     */
    public function testNotEqualFilterAlternativeSyntax(array $filter, array $expectedRows)
    {
        $key = key($filter);
        $filter[$key] = ['neq' => $filter[$key]];

        // this is a workaround for a known PDO driver issue not saving null to nullable boolean field
        // for PostgreSQL, see https://github.com/doctrine/dbal/issues/2580 for details
        if ('fieldBoolean' === $key && $this->isPostgreSql()) {
            $expectedRows = array_merge($expectedRows, [['id' => '<toString(@NullItem->id)>']]);
        }

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function notEqualFilterDataProvider()
    {
        $expectedRows = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@AnotherItem->id)>']
        ];

        return [
            'by string field'      => [
                ['fieldString' => 'Test String 2 Value'],
                $expectedRows
            ],
            'by integer field'     => [
                ['fieldInt' => '2'],
                $expectedRows
            ],
            'by smallint field'    => [
                ['fieldSmallInt' => '2'],
                $expectedRows
            ],
            'by bigint field'      => [
                ['fieldBigInt' => '234567890123456'],
                $expectedRows
            ],
            'by boolean field'     => [
                ['fieldBoolean' => 'true'],
                $expectedRows
            ],
            'by decimal field'     => [
                ['fieldDecimal' => '2.345678'],
                $expectedRows
            ],
            'by float field'       => [
                ['fieldFloat' => '2.2'],
                $expectedRows
            ],
            'by datetime field'    => [
                ['fieldDateTime' => '2010-11-01T10:12:13+00:00'],
                $expectedRows
            ],
            'by date field'        => [
                ['fieldDate' => '2010-11-01'],
                $expectedRows
            ],
            'by time field'        => [
                ['fieldTime' => '10:12:13'],
                $expectedRows
            ],
            'by guid field'        => [
                ['fieldGuid' => '12c9746c-f44d-4a84-a72c-bdf750c70568'],
                $expectedRows
            ],
            'by percent field'     => [
                ['fieldPercent' => '0.2'],
                $expectedRows
            ],
            'by money field'       => [
                ['fieldMoney' => '2.3456'],
                $expectedRows
            ],
            'by duration field'    => [
                ['fieldDuration' => '22'],
                $expectedRows
            ],
            'by money_value field' => [
                ['fieldMoneyValue' => '2.3456'],
                $expectedRows
            ],
            'by currency field'    => [
                ['fieldCurrency' => 'UAH'],
                $expectedRows
            ],
        ];
    }

    /**
     * @dataProvider equalArrayFilterDataProvider
     */
    public function testEqualArrayFilter(array $filter, array $expectedRows)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function equalArrayFilterDataProvider()
    {
        $expectedRows = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem3->id)>']
        ];

        return [
            'by integer field'     => [
                ['fieldInt' => '1,3'],
                $expectedRows
            ],
            'by smallint field'    => [
                ['fieldSmallInt' => '1,3'],
                $expectedRows
            ],
            'by bigint field'      => [
                ['fieldBigInt' => '123456789012345,345678901234567'],
                $expectedRows
            ],
            'by decimal field'     => [
                ['fieldDecimal' => '1.234567,3.456789'],
                $expectedRows
            ],
            'by float field'       => [
                ['fieldFloat' => '1.1,3.3'],
                $expectedRows
            ],
            'by guid field'        => [
                ['fieldGuid' => 'ae404bc5-c9bb-4677-9bad-21144c704734,311e3b02-3cd2-4228-aff3-bafe9b0826de'],
                $expectedRows
            ],
            'by percent field'     => [
                ['fieldPercent' => '0.1,0.3'],
                $expectedRows
            ],
            'by money field'       => [
                ['fieldMoney' => '1.2345,3.4567'],
                $expectedRows
            ],
            'by duration field'    => [
                ['fieldDuration' => '11,33'],
                $expectedRows
            ],
            'by money_value field' => [
                ['fieldMoneyValue' => '1.2345,3.4567'],
                $expectedRows
            ],
            'by currency field'    => [
                ['fieldCurrency' => 'USD,EUR'],
                $expectedRows
            ],
        ];
    }

    /**
     * @dataProvider rangeFilterDataProvider
     */
    public function testRangeFilter(array $filter, array $expectedRows)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function rangeFilterDataProvider()
    {
        $expectedRows = [
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem3->id)>']
        ];

        return [
            'by integer field'     => [
                ['fieldInt' => '2..3'],
                $expectedRows
            ],
            'by smallint field'    => [
                ['fieldSmallInt' => '2..3'],
                $expectedRows
            ],
            'by bigint field'      => [
                ['fieldBigInt' => '234567890123456..345678901234567'],
                $expectedRows
            ],
            'by decimal field'     => [
                ['fieldDecimal' => '2.2..3.5'],
                $expectedRows
            ],
            'by float field'       => [
                ['fieldFloat' => '2.2..3.5'],
                $expectedRows
            ],
            'by datetime field'    => [
                ['fieldDateTime' => '2010-11-01T10:12:13..2010-12-01T10:13:14'],
                $expectedRows
            ],
            'by date field'        => [
                ['fieldDate' => '2010-11-01..2010-12-01'],
                $expectedRows
            ],
            'by time field'        => [
                ['fieldTime' => '10:12:13..10:13:14'],
                $expectedRows
            ],
            'by percent field'     => [
                ['fieldPercent' => '0.2..0.3'],
                $expectedRows
            ],
            'by money field'       => [
                ['fieldMoney' => '2.3456..3.4567'],
                $expectedRows
            ],
            'by duration field'    => [
                ['fieldDuration' => '22..33'],
                $expectedRows
            ],
            'by money_value field' => [
                ['fieldMoneyValue' => '2.3456..3.4567'],
                $expectedRows
            ],
        ];
    }

    /**
     * @dataProvider notInRangeFilterDataProvider
     */
    public function testNotInRangeFilter(array $filter, array $expectedRows)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $key = key($filter);
        $filter[$key] = ['neq' => $filter[$key]];

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function notInRangeFilterDataProvider()
    {
        $expectedRows1 = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@AnotherItem->id)>']
        ];
        $expectedRows2 = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@AnotherItem->id)>']
        ];

        return [
            'by integer field'     => [
                ['fieldInt' => '2..3'],
                $expectedRows1
            ],
            'by smallint field'    => [
                ['fieldSmallInt' => '2..3'],
                $expectedRows1
            ],
            'by bigint field'      => [
                ['fieldBigInt' => '123456789012346..345678901234566'],
                $expectedRows2
            ],
            'by decimal field'     => [
                ['fieldDecimal' => '1.234568..3.456788'],
                $expectedRows2
            ],
            'by float field'       => [
                ['fieldFloat' => '1.1001..3.2999'],
                $expectedRows2
            ],
            'by datetime field'    => [
                ['fieldDateTime' => '2010-10-01T10:11:13..2010-12-01T10:13:13'],
                $expectedRows2
            ],
            'by date field'        => [
                ['fieldDate' => '2010-10-02..2010-11-30'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by time field'        => [
                ['fieldTime' => '10:11:13..10:13:13'],
                $expectedRows2
            ],
            'by percent field'     => [
                ['fieldPercent' => '0.101..0.299'],
                $expectedRows2
            ],
            'by money field'       => [
                ['fieldMoney' => '1.2346..3.4566'],
                $expectedRows2
            ],
            'by duration field'    => [
                ['fieldDuration' => '12..32'],
                $expectedRows2
            ],
            'by money_value field' => [
                ['fieldMoneyValue' => '1.2346..3.4566'],
                $expectedRows2
            ],
        ];
    }

    public function testFilterForTextFieldShouldBeDisabledByDefault()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $response = $this->cget(['entity' => $entityType], ['filter[fieldText]' => 'test'], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "eq" is not supported.',
                'source' => ['parameter' => 'filter[fieldText]']
            ],
            $response
        );
    }

    public function testEqualFilterForTextField()
    {
        $filter = ['fieldText' => 'Test Text 2 Value'];
        $expectedRows = [['id' => '<toString(@TestItem2->id)>']];

        $this->appendEntityConfig(
            TestAllDataTypes::class,
            ['filters' => ['fields' => ['fieldText' => ['operators' => ['=']]]]]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testNotEqualFilterForTextField()
    {
        $filter = ['fieldText' => 'Test Text 2 Value'];
        $expectedRows = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@AnotherItem->id)>']
        ];

        $this->appendEntityConfig(
            TestAllDataTypes::class,
            ['filters' => ['fields' => ['fieldText' => ['operators' => ['!=']]]]]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $key = key($filter);
        $filter = [sprintf('filter[%s]!', $key) => $filter[$key]];

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @dataProvider existsFilterDataProvider
     */
    public function testExistsFilter($filterFieldName, array $expectedRows)
    {
        $filter = ['filters' => sprintf('filter[%s]*true', $filterFieldName)];

        // this is a workaround for a known PDO driver issue not saving null to nullable boolean field
        // for PostgreSQL, see https://github.com/doctrine/dbal/issues/2580 for details
        if ('fieldBoolean' === $filterFieldName && $this->isPostgreSql()) {
            $expectedRows = array_merge($expectedRows, [['id' => '<toString(@NullItem->id)>']]);
        }

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @dataProvider existsFilterDataProvider
     */
    public function testExistsFilterAlternativeSyntax($filterFieldName, array $expectedRows)
    {
        $filter = [$filterFieldName => ['exists' => 'true']];

        // this is a workaround for a known PDO driver issue not saving null to nullable boolean field
        // for PostgreSQL, see https://github.com/doctrine/dbal/issues/2580 for details
        if ('fieldBoolean' === $filterFieldName && $this->isPostgreSql()) {
            $expectedRows = array_merge($expectedRows, [['id' => '<toString(@NullItem->id)>']]);
        }

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testExistsFilterWithNumberValue()
    {
        $filterFieldName = 'fieldString';
        $expectedRows = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@AnotherItem->id)>']
        ];

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $filter = ['filters' => sprintf('filter[%s]*1', $filterFieldName)];

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testExistsFilterAlternativeSyntaxWithNumberValue()
    {
        $filterFieldName = 'fieldString';
        $expectedRows = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@AnotherItem->id)>']
        ];

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $filter = [$filterFieldName => ['exists' => '1']];

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testExistsFilterWithStringValue()
    {
        $filterFieldName = 'fieldString';
        $expectedRows = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@AnotherItem->id)>']
        ];

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $filter = ['filters' => sprintf('filter[%s]*yes', $filterFieldName)];

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testExistsFilterAlternativeSyntaxWithStringValue()
    {
        $filterFieldName = 'fieldString';
        $expectedRows = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@AnotherItem->id)>']
        ];

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $filter = [$filterFieldName => ['exists' => 'yes']];

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function existsFilterDataProvider()
    {
        $expectedRows = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@AnotherItem->id)>']
        ];

        return [
            'by string field'      => [
                'fieldString',
                $expectedRows
            ],
            'by text field'        => [
                'fieldText',
                $expectedRows
            ],
            'by integer field'     => [
                'fieldInt',
                $expectedRows
            ],
            'by smallint field'    => [
                'fieldSmallInt',
                $expectedRows
            ],
            'by bigint field'      => [
                'fieldBigInt',
                $expectedRows
            ],
            'by boolean field'     => [
                'fieldBoolean',
                $expectedRows
            ],
            'by decimal field'     => [
                'fieldDecimal',
                $expectedRows
            ],
            'by float field'       => [
                'fieldFloat',
                $expectedRows
            ],
            'by datetime field'    => [
                'fieldDateTime',
                $expectedRows
            ],
            'by date field'        => [
                'fieldDate',
                $expectedRows
            ],
            'by time field'        => [
                'fieldTime',
                $expectedRows
            ],
            'by guid field'        => [
                'fieldGuid',
                $expectedRows
            ],
            'by percent field'     => [
                'fieldPercent',
                $expectedRows
            ],
            'by money field'       => [
                'fieldMoney',
                $expectedRows
            ],
            'by duration field'    => [
                'fieldDuration',
                $expectedRows
            ],
            'by money_value field' => [
                'fieldMoneyValue',
                $expectedRows
            ],
            'by currency field'    => [
                'fieldCurrency',
                $expectedRows
            ],
        ];
    }

    /**
     * @dataProvider notExistsFilterDataProvider
     */
    public function testNotExistsFilter($filterFieldName, array $expectedRows)
    {
        $filter = ['filters' => sprintf('filter[%s]*false', $filterFieldName)];

        // this is a workaround for a known PDO driver issue not saving null to nullable boolean field
        // for PostgreSQL, see https://github.com/doctrine/dbal/issues/2580 for details
        if ('fieldBoolean' === $filterFieldName && $this->isPostgreSql()) {
            $expectedRows = [];
        }

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @dataProvider notExistsFilterDataProvider
     */
    public function testNotExistsFilterAlternativeSyntax($filterFieldName, array $expectedRows)
    {
        $filter = [$filterFieldName => ['exists' => 'false']];

        // this is a workaround for a known PDO driver issue not saving null to nullable boolean field
        // for PostgreSQL, see https://github.com/doctrine/dbal/issues/2580 for details
        if ('fieldBoolean' === $filterFieldName && $this->isPostgreSql()) {
            $expectedRows = [];
        }

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testNotExistsFilterWithNumberValue()
    {
        $filterFieldName = 'fieldString';
        $expectedRows = [['id' => '<toString(@NullItem->id)>']];

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $filter = ['filters' => sprintf('filter[%s]*0', $filterFieldName)];

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testNotExistsFilterAlternativeSyntaxWithNumberValue()
    {
        $filterFieldName = 'fieldString';
        $expectedRows = [['id' => '<toString(@NullItem->id)>']];

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $filter = [$filterFieldName => ['exists' => '0']];

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testNotExistsFilterWithStringValue()
    {
        $filterFieldName = 'fieldString';
        $expectedRows = [['id' => '<toString(@NullItem->id)>']];

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $filter = ['filters' => sprintf('filter[%s]*no', $filterFieldName)];

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testNotExistsFilterAlternativeSyntaxWithStringValue()
    {
        $filterFieldName = 'fieldString';
        $expectedRows = [['id' => '<toString(@NullItem->id)>']];

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $filter = [$filterFieldName => ['exists' => 'no']];

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function notExistsFilterDataProvider()
    {
        $expectedRows = [['id' => '<toString(@NullItem->id)>']];

        return [
            'by string field'      => [
                'fieldString',
                $expectedRows
            ],
            'by text field'        => [
                'fieldText',
                $expectedRows
            ],
            'by integer field'     => [
                'fieldInt',
                $expectedRows
            ],
            'by smallint field'    => [
                'fieldSmallInt',
                $expectedRows
            ],
            'by bigint field'      => [
                'fieldBigInt',
                $expectedRows
            ],
            'by boolean field'     => [
                'fieldBoolean',
                $expectedRows
            ],
            'by decimal field'     => [
                'fieldDecimal',
                $expectedRows
            ],
            'by float field'       => [
                'fieldFloat',
                $expectedRows
            ],
            'by datetime field'    => [
                'fieldDateTime',
                $expectedRows
            ],
            'by date field'        => [
                'fieldDate',
                $expectedRows
            ],
            'by time field'        => [
                'fieldTime',
                $expectedRows
            ],
            'by guid field'        => [
                'fieldGuid',
                $expectedRows
            ],
            'by percent field'     => [
                'fieldPercent',
                $expectedRows
            ],
            'by money field'       => [
                'fieldMoney',
                $expectedRows
            ],
            'by duration field'    => [
                'fieldDuration',
                $expectedRows
            ],
            'by money_value field' => [
                'fieldMoneyValue',
                $expectedRows
            ],
            'by currency field'    => [
                'fieldCurrency',
                $expectedRows
            ],
        ];
    }

    /**
     * @dataProvider notEqualOrNullFilterDataProvider
     */
    public function testNotEqualOrNullFilter(array $filter, array $expectedRows)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $key = key($filter);
        $filter = ['filters' => sprintf('filter[%s]!*%s', $key, urlencode($filter[$key]))];

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @dataProvider notEqualOrNullFilterDataProvider
     */
    public function testNotEqualOrNullFilterAlternativeSyntax(array $filter, array $expectedRows)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $key = key($filter);
        $filter[$key] = ['neq_or_null' => $filter[$key]];

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function notEqualOrNullFilterDataProvider()
    {
        $expectedRows = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@AnotherItem->id)>'],
            ['id' => '<toString(@NullItem->id)>']
        ];

        return [
            'by string field'      => [
                ['fieldString' => 'Test String 2 Value'],
                $expectedRows
            ],
            'by integer field'     => [
                ['fieldInt' => '2'],
                $expectedRows
            ],
            'by smallint field'    => [
                ['fieldSmallInt' => '2'],
                $expectedRows
            ],
            'by bigint field'      => [
                ['fieldBigInt' => '234567890123456'],
                $expectedRows
            ],
            'by boolean field'     => [
                ['fieldBoolean' => 'true'],
                $expectedRows
            ],
            'by decimal field'     => [
                ['fieldDecimal' => '2.345678'],
                $expectedRows
            ],
            'by float field'       => [
                ['fieldFloat' => '2.2'],
                $expectedRows
            ],
            'by datetime field'    => [
                ['fieldDateTime' => '2010-11-01T10:12:13+00:00'],
                $expectedRows
            ],
            'by date field'        => [
                ['fieldDate' => '2010-11-01'],
                $expectedRows
            ],
            'by time field'        => [
                ['fieldTime' => '10:12:13'],
                $expectedRows
            ],
            'by guid field'        => [
                ['fieldGuid' => '12c9746c-f44d-4a84-a72c-bdf750c70568'],
                $expectedRows
            ],
            'by percent field'     => [
                ['fieldPercent' => '0.2'],
                $expectedRows
            ],
            'by money field'       => [
                ['fieldMoney' => '2.3456'],
                $expectedRows
            ],
            'by duration field'    => [
                ['fieldDuration' => '22'],
                $expectedRows
            ],
            'by money_value field' => [
                ['fieldMoneyValue' => '2.3456'],
                $expectedRows
            ],
            'by currency field'    => [
                ['fieldCurrency' => 'UAH'],
                $expectedRows
            ],
        ];
    }

    public function testContainsFilterShouldBeDisabledByDefault()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $response = $this->cget(['entity' => $entityType], ['filter[fieldString]~' => 'test'], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "contains" is not supported.',
                'source' => ['parameter' => 'filter[fieldString]']
            ],
            $response
        );
    }

    public function testNotContainsFilterShouldBeDisabledByDefault()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $response = $this->cget(['entity' => $entityType], ['filter[fieldString]!~' => 'test'], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "not_contains" is not supported.',
                'source' => ['parameter' => 'filter[fieldString]']
            ],
            $response
        );
    }

    public function testStartsWithFilterShouldBeDisabledByDefault()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $response = $this->cget(['entity' => $entityType], ['filter[fieldString]^' => 'test'], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "starts_with" is not supported.',
                'source' => ['parameter' => 'filter[fieldString]']
            ],
            $response
        );
    }

    public function testNotStartsWithFilterShouldBeDisabledByDefault()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $response = $this->cget(['entity' => $entityType], ['filter[fieldString]!^' => 'test'], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "not_starts_with" is not supported.',
                'source' => ['parameter' => 'filter[fieldString]']
            ],
            $response
        );
    }

    public function testEndsWithFilterShouldBeDisabledByDefault()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $response = $this->cget(['entity' => $entityType], ['filter[fieldString]$' => 'test'], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "ends_with" is not supported.',
                'source' => ['parameter' => 'filter[fieldString]']
            ],
            $response
        );
    }

    public function testNotEndsWithFilterShouldBeDisabledByDefault()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $response = $this->cget(['entity' => $entityType], ['filter[fieldString]!$' => 'test'], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "not_ends_with" is not supported.',
                'source' => ['parameter' => 'filter[fieldString]']
            ],
            $response
        );
    }

    /**
     * @dataProvider containsFilterDataProvider
     */
    public function testContainsFilter(array $filter, array $expectedRows)
    {
        $filterFieldName = key($filter);
        $filter = ['filters' => sprintf('filter[%s]~%s', $filterFieldName, $filter[$filterFieldName])];

        $this->appendEntityConfig(
            TestAllDataTypes::class,
            ['filters' => ['fields' => [$filterFieldName => ['operators' => ['~']]]]]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @dataProvider containsFilterDataProvider
     */
    public function testContainsFilterAlternativeSyntax(array $filter, array $expectedRows)
    {
        $filterFieldName = key($filter);
        $filter = [sprintf('filter[%s][contains]', $filterFieldName) => $filter[$filterFieldName]];

        $this->appendEntityConfig(
            TestAllDataTypes::class,
            ['filters' => ['fields' => [$filterFieldName => ['operators' => ['~']]]]]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function containsFilterDataProvider()
    {
        $expectedRows = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem3->id)>']
        ];

        return [
            'by string field' => [
                ['fieldString' => 'String'],
                $expectedRows
            ],
            'by text field'   => [
                ['fieldText' => 'Text'],
                $expectedRows
            ]
        ];
    }

    /**
     * @dataProvider notContainsFilterDataProvider
     */
    public function testNotContainsFilter(array $filter, array $expectedRows)
    {
        $filterFieldName = key($filter);
        $filter = ['filters' => sprintf('filter[%s]!~%s', $filterFieldName, $filter[$filterFieldName])];

        $this->appendEntityConfig(
            TestAllDataTypes::class,
            ['filters' => ['fields' => [$filterFieldName => ['operators' => ['!~']]]]]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @dataProvider notContainsFilterDataProvider
     */
    public function testNotContainsFilterAlternativeSyntax(array $filter, array $expectedRows)
    {
        $filterFieldName = key($filter);
        $filter = [sprintf('filter[%s][not_contains]', $filterFieldName) => $filter[$filterFieldName]];

        $this->appendEntityConfig(
            TestAllDataTypes::class,
            ['filters' => ['fields' => [$filterFieldName => ['operators' => ['!~']]]]]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function notContainsFilterDataProvider()
    {
        $expectedRows = [['id' => '<toString(@AnotherItem->id)>']];

        return [
            'by string field' => [
                ['fieldString' => 'String'],
                $expectedRows
            ],
            'by text field'   => [
                ['fieldText' => 'Text'],
                $expectedRows
            ]
        ];
    }

    /**
     * @dataProvider startsWithFilterDataProvider
     */
    public function testStartsWithFilter(array $filter, array $expectedRows)
    {
        $filterFieldName = key($filter);
        $filter = ['filters' => sprintf('filter[%s]^%s', $filterFieldName, $filter[$filterFieldName])];

        $this->appendEntityConfig(
            TestAllDataTypes::class,
            ['filters' => ['fields' => [$filterFieldName => ['operators' => ['^']]]]]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @dataProvider startsWithFilterDataProvider
     */
    public function testStartsWithFilterAlternativeSyntax(array $filter, array $expectedRows)
    {
        $filterFieldName = key($filter);
        $filter = [sprintf('filter[%s][starts_with]', $filterFieldName) => $filter[$filterFieldName]];

        $this->appendEntityConfig(
            TestAllDataTypes::class,
            ['filters' => ['fields' => [$filterFieldName => ['operators' => ['^']]]]]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function startsWithFilterDataProvider()
    {
        $expectedRows = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem3->id)>']
        ];

        return [
            'by string field' => [
                ['fieldString' => 'Test'],
                $expectedRows
            ],
            'by text field'   => [
                ['fieldText' => 'Test'],
                $expectedRows
            ]
        ];
    }

    /**
     * @dataProvider notStartsWithFilterDataProvider
     */
    public function testNotStartsWithFilter(array $filter, array $expectedRows)
    {
        $filterFieldName = key($filter);
        $filter = ['filters' => sprintf('filter[%s]!^%s', $filterFieldName, $filter[$filterFieldName])];

        $this->appendEntityConfig(
            TestAllDataTypes::class,
            ['filters' => ['fields' => [$filterFieldName => ['operators' => ['!^']]]]]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @dataProvider notStartsWithFilterDataProvider
     */
    public function testNotStartsWithFilterAlternativeSyntax(array $filter, array $expectedRows)
    {
        $filterFieldName = key($filter);
        $filter = [sprintf('filter[%s][not_starts_with]', $filterFieldName) => $filter[$filterFieldName]];

        $this->appendEntityConfig(
            TestAllDataTypes::class,
            ['filters' => ['fields' => [$filterFieldName => ['operators' => ['!^']]]]]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function notStartsWithFilterDataProvider()
    {
        $expectedRows = [['id' => '<toString(@AnotherItem->id)>']];

        return [
            'by string field' => [
                ['fieldString' => 'Test'],
                $expectedRows
            ],
            'by text field'   => [
                ['fieldText' => 'Test'],
                $expectedRows
            ]
        ];
    }

    /**
     * @dataProvider endsWithFilterDataProvider
     */
    public function testEndsWithFilter(array $filter, array $expectedRows)
    {
        $filterFieldName = key($filter);
        $filter = ['filters' => sprintf('filter[%s]$%s', $filterFieldName, $filter[$filterFieldName])];

        $this->appendEntityConfig(
            TestAllDataTypes::class,
            ['filters' => ['fields' => [$filterFieldName => ['operators' => ['$']]]]]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @dataProvider endsWithFilterDataProvider
     */
    public function testEndsWithFilterAlternativeSyntax(array $filter, array $expectedRows)
    {
        $filterFieldName = key($filter);
        $filter = [sprintf('filter[%s][ends_with]', $filterFieldName) => $filter[$filterFieldName]];

        $this->appendEntityConfig(
            TestAllDataTypes::class,
            ['filters' => ['fields' => [$filterFieldName => ['operators' => ['$']]]]]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function endsWithFilterDataProvider()
    {
        $expectedRows = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem3->id)>']
        ];

        return [
            'by string field' => [
                ['fieldString' => 'Value'],
                $expectedRows
            ],
            'by text field'   => [
                ['fieldText' => 'Value'],
                $expectedRows
            ]
        ];
    }

    /**
     * @dataProvider notEndsWithFilterDataProvider
     */
    public function testNotEndsWithFilter(array $filter, array $expectedRows)
    {
        $filterFieldName = key($filter);
        $filter = ['filters' => sprintf('filter[%s]!$%s', $filterFieldName, $filter[$filterFieldName])];

        $this->appendEntityConfig(
            TestAllDataTypes::class,
            ['filters' => ['fields' => [$filterFieldName => ['operators' => ['!$']]]]]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @dataProvider notEndsWithFilterDataProvider
     */
    public function testNotEndsWithFilterAlternativeSyntax(array $filter, array $expectedRows)
    {
        $filterFieldName = key($filter);
        $filter = [sprintf('filter[%s][not_ends_with]', $filterFieldName) => $filter[$filterFieldName]];

        $this->appendEntityConfig(
            TestAllDataTypes::class,
            ['filters' => ['fields' => [$filterFieldName => ['operators' => ['!$']]]]]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function notEndsWithFilterDataProvider()
    {
        $expectedRows = [['id' => '<toString(@AnotherItem->id)>']];

        return [
            'by string field' => [
                ['fieldString' => 'Value'],
                $expectedRows
            ],
            'by text field'   => [
                ['fieldText' => 'Value'],
                $expectedRows
            ]
        ];
    }

    public function testCaseInsensitiveFilter()
    {
        $this->appendEntityConfig(
            TestAllDataTypes::class,
            [
                'filters' => [
                    'fields' => [
                        'fieldString' => ['options' => ['case_insensitive' => true]]
                    ]
                ]
            ]
        );

        $filter = ['fieldString' => 'Test STRING 2 VALUE'];
        $expectedRows = [['id' => '<toString(@TestItem2->id)>']];

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testFilterWithValueTransformer()
    {
        $this->appendEntityConfig(
            TestAllDataTypes::class,
            [
                'filters' => [
                    'fields' => [
                        'fieldString' => ['options' => ['value_transformer' => 'ucwords']]
                    ]
                ]
            ]
        );

        $filter = ['fieldString' => 'test string 2 value'];
        $expectedRows = [['id' => '<toString(@TestItem2->id)>']];

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testFilterWithUrlEncodedQueryString()
    {
        /** @var TestAllDataTypes $entity */
        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getReference('TestItem2')->id);
        $entity->fieldString = 'Test String@2';
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $filter = 'filter%5BfieldString%5D%3DTest+String%402';
        $expectedRows = [['id' => '<toString(@TestItem2->id)>']];

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $url = $this->getUrl($this->getListRouteName(), ['entity' => $entityType]);
        $url .= '?' . $filter;
        $response = $this->request('GET', $url);

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testFilterAlternativeSyntaxWithUrlEncodedQueryString()
    {
        /** @var TestAllDataTypes $entity */
        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getReference('TestItem2')->id);
        $entity->fieldString = 'Test String@2';
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $filter = 'filter%5BfieldString%5D%5Beq%5D%3DTest+String%402';
        $expectedRows = [['id' => '<toString(@TestItem2->id)>']];

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $url = $this->getUrl($this->getListRouteName(), ['entity' => $entityType]);
        $url .= '?' . $filter;
        $response = $this->request('GET', $url);

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        $this->assertResponseContains(['data' => $expectedRows], $response);
    }
}
