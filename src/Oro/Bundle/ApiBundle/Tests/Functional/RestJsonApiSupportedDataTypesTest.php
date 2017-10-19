<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestAllDataTypes;

/**
 * @dbIsolationPerTest
 */
class RestJsonApiSupportedDataTypesTest extends RestJsonApiTestCase
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

    public function testGet()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => $entityType,
                    'id'         => '<toString(@TestItem1->id)>',
                    'attributes' => [
                        'fieldString'      => 'String 1',
                        'fieldText'        => 'Text 1',
                        'fieldInt'         => 1,
                        'fieldSmallInt'    => 1,
                        'fieldBigInt'      => '123456789012345',
                        'fieldBoolean'     => false,
                        'fieldDecimal'     => '1.234567',
                        'fieldFloat'       => 1.1,
                        'fieldArray'       => [1, 2, 3],
                        'fieldSimpleArray' => ['1', '2', '3'],
                        'fieldJsonArray'   => ['key1' => 'value1'],
                        'fieldDateTime'    => '2010-10-01T10:11:12Z',
                        'fieldDate'        => '2010-10-01',
                        'fieldTime'        => '10:11:12',
                        'fieldGuid'        => 'ae404bc5-c9bb-4677-9bad-21144c704734',
                        'fieldPercent'     => 0.1,
                        'fieldMoney'       => '1.2345',
                        'fieldDuration'    => 11,
                        'fieldMoneyValue'  => '1.2345',
                        'fieldCurrency'    => 'USD',
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreate()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'attributes' => [
                    'fieldString'      => 'New String',
                    'fieldText'        => 'Some Text',
                    'fieldInt'         => 2147483647,
                    'fieldSmallInt'    => 32767,
                    'fieldBigInt'      => '9223372036854775807',
                    'fieldBoolean'     => true,
                    'fieldDecimal'     => '123.456789',
                    'fieldFloat'       => 123.456789,
                    'fieldArray'       => [1, 2, 3],
                    'fieldSimpleArray' => ['1', '2', '3'],
                    'fieldJsonArray'   => ['key' => 'value'],
                    'fieldDateTime'    => '2017-01-21T10:20:30Z',
                    'fieldDate'        => '2017-01-21',
                    'fieldTime'        => '10:20:30',
                    'fieldGuid'        => '6f690d83-9b60-4da4-9c47-1b163229db6d',
                    //'fieldPercent'     => 0.123, TODO: uncomment after merge BAP-13666
                    'fieldMoney'       => '123.4567',
                    'fieldDuration'    => 123,
                    'fieldMoneyValue'  => '123.4567',
                    'fieldCurrency'    => 'USD',
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $this->assertResponseContains($data, $response);

        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getResourceId($response));
        $expectedEntityData = $data['data']['attributes'];
        $expectedEntityData['fieldDateTime'] = str_replace('Z', '+0000', $expectedEntityData['fieldDateTime']);
        self::assertArrayContains(
            $expectedEntityData,
            [
                'fieldString'      => $entity->fieldString,
                'fieldText'        => $entity->fieldText,
                'fieldInt'         => $entity->fieldInt,
                'fieldSmallInt'    => $entity->fieldSmallInt,
                'fieldBigInt'      => $entity->fieldBigInt,
                'fieldBoolean'     => $entity->fieldBoolean,
                'fieldDecimal'     => $entity->fieldDecimal,
                'fieldFloat'       => $entity->fieldFloat,
                'fieldArray'       => $entity->fieldArray,
                'fieldSimpleArray' => $entity->fieldSimpleArray,
                'fieldJsonArray'   => $entity->fieldJsonArray,
                'fieldDateTime'    => $entity->fieldDateTime->format('Y-m-d\TH:i:sO'),
                'fieldDate'        => $entity->fieldDate->format('Y-m-d'),
                'fieldTime'        => $entity->fieldTime->format('H:i:s'),
                'fieldGuid'        => $entity->fieldGuid,
                //'fieldPercent'     => $entity->fieldPercent, TODO: uncomment after merge BAP-13666
                'fieldMoney'       => $entity->fieldMoney,
                'fieldDuration'    => $entity->fieldDuration,
                'fieldMoneyValue'  => $entity->fieldMoneyValue,
                'fieldCurrency'    => $entity->fieldCurrency,
            ]
        );
    }

    public function testUpdate()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    'fieldString'      => 'New String',
                    'fieldText'        => 'Some Text',
                    'fieldInt'         => -2147483648,
                    'fieldSmallInt'    => -32768,
                    'fieldBigInt'      => '-9223372036854775808',
                    'fieldBoolean'     => true,
                    'fieldDecimal'     => '123.456789',
                    'fieldFloat'       => 123.456789,
                    'fieldArray'       => [1, 2, 3],
                    'fieldSimpleArray' => ['1', '2', '3'],
                    'fieldJsonArray'   => ['key' => 'value'],
                    'fieldDateTime'    => '2017-01-21T10:20:30Z',
                    'fieldDate'        => '2017-01-21',
                    'fieldTime'        => '10:20:30',
                    'fieldGuid'        => '6f690d83-9b60-4da4-9c47-1b163229db6d',
                    //'fieldPercent'     => 0.123, TODO: uncomment after merge BAP-13666
                    'fieldMoney'       => '123.4567',
                    'fieldDuration'    => 123,
                    'fieldMoneyValue'  => '123.4567',
                    'fieldCurrency'    => 'UAH',
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data);

        $this->assertResponseContains($data, $response);

        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getResourceId($response));
        $expectedEntityData = $data['data']['attributes'];
        $expectedEntityData['fieldDateTime'] = str_replace('Z', '+0000', $expectedEntityData['fieldDateTime']);
        self::assertArrayContains(
            $expectedEntityData,
            [
                'fieldString'      => $entity->fieldString,
                'fieldText'        => $entity->fieldText,
                'fieldInt'         => $entity->fieldInt,
                'fieldSmallInt'    => $entity->fieldSmallInt,
                'fieldBigInt'      => $entity->fieldBigInt,
                'fieldBoolean'     => $entity->fieldBoolean,
                'fieldDecimal'     => $entity->fieldDecimal,
                'fieldFloat'       => $entity->fieldFloat,
                'fieldArray'       => $entity->fieldArray,
                'fieldSimpleArray' => $entity->fieldSimpleArray,
                'fieldJsonArray'   => $entity->fieldJsonArray,
                'fieldDateTime'    => $entity->fieldDateTime->format('Y-m-d\TH:i:sO'),
                'fieldDate'        => $entity->fieldDate->format('Y-m-d'),
                'fieldTime'        => $entity->fieldTime->format('H:i:s'),
                'fieldGuid'        => $entity->fieldGuid,
                //'fieldPercent'     => $entity->fieldPercent, TODO: uncomment after merge BAP-13666
                'fieldMoney'       => $entity->fieldMoney,
                'fieldDuration'    => $entity->fieldDuration,
                'fieldMoneyValue'  => $entity->fieldMoneyValue,
                'fieldCurrency'    => $entity->fieldCurrency,
            ]
        );
    }

    public function testMoneyShouldBeRounded()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    'fieldMoney' => '123.456789'
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data);

        $expectedData = $data;
        $expectedData['data']['attributes']['fieldMoney'] = '123.4568';
        $this->assertResponseContains($expectedData, $response);

        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getResourceId($response));
        self::assertArrayContains(
            $expectedData['data']['attributes'],
            ['fieldMoney' => $entity->fieldMoney]
        );
    }

    public function testMoneyValueShouldBeRounded()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    'fieldMoneyValue' => '123.456789'
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data);

        $expectedData = $data;
        $expectedData['data']['attributes']['fieldMoneyValue'] = '123.4568';
        $this->assertResponseContains($expectedData, $response);

        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getResourceId($response));
        self::assertArrayContains(
            $expectedData['data']['attributes'],
            ['fieldMoneyValue' => $entity->fieldMoneyValue]
        );
    }

    /**
     * @dataProvider equalFilterDataProvider
     */
    public function testEqualFilter(array $filter, array $expectedRows)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        foreach ($expectedRows as &$row) {
            $row['type'] = $entityType;
        }

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @dataProvider equalFilterDataProvider
     */
    public function testEqualFilterAlternativeSyntax(array $filter, array $expectedRows)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        foreach ($expectedRows as &$row) {
            $row['type'] = $entityType;
        }

        $key = key($filter);
        $filter[$key] = ['eq' => $filter[$key]];

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function equalFilterDataProvider()
    {
        return [
            'by string field'      => [
                ['fieldString' => 'String 2'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by integer field'     => [
                ['fieldInt' => '2'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by smallint field'    => [
                ['fieldSmallInt' => '2'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by bigint field'      => [
                ['fieldBigInt' => '234567890123456'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by boolean field'     => [
                ['fieldBoolean' => 'true'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by decimal field'     => [
                ['fieldDecimal' => '2.345678'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by float field'       => [
                ['fieldFloat' => '2.2'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by datetime field'    => [
                ['fieldDateTime' => '2010-11-01T10:12:13+00:00'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by date field'        => [
                ['fieldDate' => '2010-11-01'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by time field'        => [
                ['fieldTime' => '10:12:13'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by guid field'        => [
                ['fieldGuid' => '12c9746c-f44d-4a84-a72c-bdf750c70568'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by percent field'     => [
                ['fieldPercent' => '0.2'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by money field'       => [
                ['fieldMoney' => '2.3456'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by duration field'    => [
                ['fieldDuration' => '22'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by money_value field' => [
                ['fieldMoneyValue' => '2.3456'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
            'by currency field'    => [
                ['fieldCurrency' => 'UAH'],
                [['id' => '<toString(@TestItem2->id)>']]
            ],
        ];
    }

    /**
     * @dataProvider notEqualFilterDataProvider
     */
    public function testNotEqualFilter(array $filter, array $expectedRows)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        foreach ($expectedRows as &$row) {
            $row['type'] = $entityType;
        }

        $key = key($filter);
        $filter = [sprintf('filter[%s]!', $key) => $filter[$key]];

        $response = $this->cget(['entity' => $entityType], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @dataProvider notEqualFilterDataProvider
     */
    public function testNotEqualFilterAlternativeSyntax(array $filter, array $expectedRows)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        foreach ($expectedRows as &$row) {
            $row['type'] = $entityType;
        }

        $key = key($filter);
        $filter[$key] = ['neq' => $filter[$key]];

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function notEqualFilterDataProvider()
    {
        return [
            'by string field'      => [
                ['fieldString' => 'String 2'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by integer field'     => [
                ['fieldInt' => '2'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by smallint field'    => [
                ['fieldSmallInt' => '2'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by bigint field'      => [
                ['fieldBigInt' => '234567890123456'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by boolean field'     => [
                ['fieldBoolean' => 'true'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by decimal field'     => [
                ['fieldDecimal' => '2.345678'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by float field'       => [
                ['fieldFloat' => '2.2'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by datetime field'    => [
                ['fieldDateTime' => '2010-11-01T10:12:13+00:00'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by date field'        => [
                ['fieldDate' => '2010-11-01'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by time field'        => [
                ['fieldTime' => '10:12:13'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by guid field'        => [
                ['fieldGuid' => '12c9746c-f44d-4a84-a72c-bdf750c70568'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by percent field'     => [
                ['fieldPercent' => '0.2'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by money field'       => [
                ['fieldMoney' => '2.3456'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by duration field'    => [
                ['fieldDuration' => '22'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by money_value field' => [
                ['fieldMoneyValue' => '2.3456'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by currency field'    => [
                ['fieldCurrency' => 'UAH'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
        ];
    }

    /**
     * @dataProvider equalArrayFilterDataProvider
     */
    public function testEqualArrayFilter(array $filter, array $expectedRows)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        foreach ($expectedRows as &$row) {
            $row['type'] = $entityType;
        }

        $response = $this->cget(['entity' => $entityType], ['filter' => $filter]);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function equalArrayFilterDataProvider()
    {
        return [
            'by string field'      => [
                ['fieldString' => 'String 1,String 3'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by integer field'     => [
                ['fieldInt' => '1,3'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by smallint field'    => [
                ['fieldSmallInt' => '1,3'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by bigint field'      => [
                ['fieldBigInt' => '123456789012345,345678901234567'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by decimal field'     => [
                ['fieldDecimal' => '1.234567,3.456789'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by float field'       => [
                ['fieldFloat' => '1.1,3.3'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by guid field'        => [
                ['fieldGuid' => 'ae404bc5-c9bb-4677-9bad-21144c704734,311e3b02-3cd2-4228-aff3-bafe9b0826de'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by percent field'     => [
                ['fieldPercent' => '0.1,0.3'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by money field'       => [
                ['fieldMoney' => '1.2345,3.4567'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by duration field'    => [
                ['fieldDuration' => '11,33'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by money_value field' => [
                ['fieldMoneyValue' => '1.2345,3.4567'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
            'by currency field'    => [
                ['fieldCurrency' => 'USD,EUR'],
                [['id' => '<toString(@TestItem1->id)>'], ['id' => '<toString(@TestItem3->id)>']]
            ],
        ];
    }
}
