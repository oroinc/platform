<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestAllDataTypes;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class SupportedDataTypesTest extends RestJsonApiTestCase
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
                        'fieldString'      => 'Test String 1 Value',
                        'fieldText'        => 'Test Text 1 Value',
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
                    'fieldPercent'     => 0.123,
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
                'fieldPercent'     => $entity->fieldPercent,
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
                    'fieldPercent'     => 0.123,
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
                'fieldPercent'     => $entity->fieldPercent,
                'fieldMoney'       => $entity->fieldMoney,
                'fieldDuration'    => $entity->fieldDuration,
                'fieldMoneyValue'  => $entity->fieldMoneyValue,
                'fieldCurrency'    => $entity->fieldCurrency,
            ]
        );
    }

    public function testShouldAcceptTimezoneInDateTimeField()
    {
        $inputDateTime = '2017-01-21T10:20:30+05:00';
        $utcDateTime = '2017-01-21T05:20:30+0000';
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    'fieldDateTime' => $inputDateTime
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data);

        $expectedResponseData = $data;
        $expectedResponseData['data']['attributes']['fieldDateTime'] = str_replace('+0000', 'Z', $utcDateTime);
        $this->assertResponseContains($expectedResponseData, $response);

        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getResourceId($response));
        self::assertEquals($utcDateTime, $entity->fieldDateTime->format('Y-m-d\TH:i:sO'));
    }

    public function testShouldAcceptDateOnlyInDateTimeField()
    {
        $inputDateTime = '2017-01-21';
        $utcDateTime = '2017-01-21T00:00:00+0000';
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    'fieldDateTime' => $inputDateTime
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data);

        $expectedResponseData = $data;
        $expectedResponseData['data']['attributes']['fieldDateTime'] = str_replace('+0000', 'Z', $utcDateTime);
        $this->assertResponseContains($expectedResponseData, $response);

        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getResourceId($response));
        self::assertEquals($utcDateTime, $entity->fieldDateTime->format('Y-m-d\TH:i:sO'));
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
}
