<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestAllDataTypes;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class SupportedDataTypesTest extends RestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/supported_data_types.yml'
        ]);
    }

    /**
     * @return bool
     */
    private function isPostgreSql()
    {
        return $this->getEntityManager()->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform;
    }

    /**
     * @param int $entityId
     *
     * @return array
     */
    private function getEntityData($entityId)
    {
        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $entityId);

        $fieldDateTime = $entity->fieldDateTime;
        if (null !== $fieldDateTime) {
            $fieldDateTime = $entity->fieldDateTime->format('Y-m-d\TH:i:sO');
        }
        $fieldDate = $entity->fieldDate;
        if (null !== $fieldDate) {
            $fieldDate = $entity->fieldDate->format('Y-m-d');
        }
        $fieldTime = $entity->fieldTime;
        if (null !== $fieldTime) {
            $fieldTime = $entity->fieldTime->format('H:i:s');
        }

        return [
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
            'fieldDateTime'    => $fieldDateTime,
            'fieldDate'        => $fieldDate,
            'fieldTime'        => $fieldTime,
            'fieldGuid'        => $entity->fieldGuid,
            'fieldPercent'     => $entity->fieldPercent,
            'fieldMoney'       => $entity->fieldMoney,
            'fieldDuration'    => $entity->fieldDuration,
            'fieldMoneyValue'  => $entity->fieldMoneyValue,
            'fieldCurrency'    => $entity->fieldCurrency
        ];
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
                        'fieldCurrency'    => 'USD'
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
                    'fieldCurrency'    => 'USD'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $this->assertResponseContains($data, $response);

        $expectedEntityData = $data['data']['attributes'];
        $expectedEntityData['fieldDateTime'] = str_replace('Z', '+0000', $expectedEntityData['fieldDateTime']);
        self::assertArrayContains(
            $expectedEntityData,
            $this->getEntityData((int)$this->getResourceId($response))
        );
    }

    public function testCreateWithoutData()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type' => $entityType
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $this->assertResponseContains($data, $response);

        $fieldBooleanNullValue = null;
        // this is a workaround for a known PDO driver issue not saving null to nullable boolean field
        // for PostgreSQL, see https://github.com/doctrine/dbal/issues/2580 for details
        if ($this->isPostgreSql()) {
            $fieldBooleanNullValue = false;
        }

        self::assertArrayContains(
            [
                'fieldString'      => null,
                'fieldText'        => null,
                'fieldInt'         => null,
                'fieldSmallInt'    => null,
                'fieldBigInt'      => null,
                'fieldBoolean'     => $fieldBooleanNullValue,
                'fieldDecimal'     => null,
                'fieldFloat'       => null,
                'fieldArray'       => null,
                'fieldSimpleArray' => [],
                'fieldJsonArray'   => [],
                'fieldDateTime'    => null,
                'fieldDate'        => null,
                'fieldTime'        => null,
                'fieldGuid'        => null,
                'fieldPercent'     => null,
                'fieldMoney'       => null,
                'fieldDuration'    => null,
                'fieldMoneyValue'  => null,
                'fieldCurrency'    => null
            ],
            $this->getEntityData((int)$this->getResourceId($response))
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
                    'fieldCurrency'    => 'UAH'
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data);

        $this->assertResponseContains($data, $response);

        $expectedEntityData = $data['data']['attributes'];
        $expectedEntityData['fieldDateTime'] = str_replace('Z', '+0000', $expectedEntityData['fieldDateTime']);
        self::assertArrayContains(
            $expectedEntityData,
            $this->getEntityData((int)$this->getResourceId($response))
        );
    }

    /**
     * @dataProvider emptyDataProvider
     */
    public function testCreateShouldAcceptEmptyValue($fieldName, $value, $responseValue, $entityValue)
    {
        // this is a workaround for a known PDO driver issue not saving null to nullable boolean field
        // for PostgreSQL, see https://github.com/doctrine/dbal/issues/2580 for details
        if ('fieldBoolean' === $fieldName && null === $entityValue && $this->isPostgreSql()) {
            $entityValue = false;
        }

        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'attributes' => [
                    $fieldName => $value
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $responseContent = self::jsonToArray($response->getContent());
        self::assertSame($responseValue, $responseContent['data']['attributes'][$fieldName], 'response data');

        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getResourceId($response));
        self::assertSame($entityValue, $entity->{$fieldName}, 'entity data');
    }

    /**
     * @dataProvider emptyDataProvider
     */
    public function testUpdateShouldAcceptEmptyValue($fieldName, $value, $responseValue, $entityValue)
    {
        // this is a workaround for a known PDO driver issue not saving null to nullable boolean field
        // for PostgreSQL, see https://github.com/doctrine/dbal/issues/2580 for details
        if ('fieldBoolean' === $fieldName && null === $entityValue && $this->isPostgreSql()) {
            $entityValue = false;
        }

        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    $fieldName => $value
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data);

        $responseContent = self::jsonToArray($response->getContent());
        self::assertSame($responseValue, $responseContent['data']['attributes'][$fieldName], 'response data');

        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getResourceId($response));
        self::assertSame($entityValue, $entity->{$fieldName}, 'entity data');
    }

    /**
     * @return array
     */
    public function emptyDataProvider()
    {
        return [
            'String NULL'             => ['fieldString', null, null, null],
            'String Empty'            => ['fieldString', '', '', ''],
            'Text NULL'               => ['fieldText', null, null, null],
            'Text Empty'              => ['fieldText', '', '', ''],
            'Int NULL'                => ['fieldInt', null, null, null],
            'Int Zero'                => ['fieldInt', 0, 0, 0],
            'Int Zero (string)'       => ['fieldInt', '0', 0, 0],
            'SmallInt NULL'           => ['fieldSmallInt', null, null, null],
            'SmallInt Zero'           => ['fieldSmallInt', 0, 0, 0],
            'SmallInt Zero (string)'  => ['fieldSmallInt', '0', 0, 0],
            'BigInt NULL'             => ['fieldBigInt', null, null, null],
            'BigInt Zero'             => ['fieldBigInt', '0', '0', '0'],
            'BigInt Zero (int)'       => ['fieldBigInt', 0, '0', '0'],
            'Boolean NULL'            => ['fieldBoolean', null, null, null],
            'Boolean FALSE'           => ['fieldBoolean', false, false, false],
            'Decimal NULL'            => ['fieldDecimal', null, null, null],
            'Decimal Zero'            => ['fieldDecimal', '0', '0', '0.000000'],
            'Decimal Zero (int)'      => ['fieldDecimal', 0, '0', '0.000000'],
            'Decimal Zero (float)'    => ['fieldDecimal', 0.0, '0', '0.000000'],
            'Float NULL'              => ['fieldFloat', null, null, null],
            'Float Zero'              => ['fieldFloat', 0.0, 0, 0.0],
            'Float Zero (string)'     => ['fieldFloat', '0', 0, 0.0],
            'Float Zero (int)'        => ['fieldFloat', 0, 0, 0.0],
            'Array NULL'              => ['fieldArray', null, null, null],
            'Array Empty'             => ['fieldArray', [], [], []],
            'SimpleArray NULL'        => ['fieldSimpleArray', null, null, []],
            'SimpleArray Empty'       => ['fieldSimpleArray', [], [], []],
            'JsonArray NULL'          => ['fieldJsonArray', null, null, []],
            'JsonArray Empty'         => ['fieldJsonArray', [], null, []],
            'DateTime NULL'           => ['fieldDateTime', null, null, null],
            'Date NULL'               => ['fieldDate', null, null, null],
            'Time NULL'               => ['fieldTime', null, null, null],
            'Guid NULL'               => ['fieldGuid', null, null, null],
            'Percent NULL'            => ['fieldPercent', null, null, null],
            'Percent Zero'            => ['fieldPercent', 0.0, 0, 0.0],
            'Percent Zero (string)'   => ['fieldPercent', '0', 0, 0.0],
            'Percent Zero (int)'      => ['fieldPercent', 0, 0, 0.0],
            'Money NULL'              => ['fieldMoney', null, null, null],
            'Money Zero'              => ['fieldMoney', '0', '0', '0.0000'],
            'Money Zero (int)'        => ['fieldMoney', 0, '0', '0.0000'],
            'Money Zero (float)'      => ['fieldMoney', 0.0, '0', '0.0000'],
            'Duration NULL'           => ['fieldDuration', null, null, null],
            'Duration Zero'           => ['fieldDuration', 0, 0, 0],
            'Duration Zero (string)'  => ['fieldDuration', '0', 0, 0],
            'MoneyValue NULL'         => ['fieldMoneyValue', null, null, null],
            'MoneyValue Zero'         => ['fieldMoneyValue', '0', '0', '0.0000'],
            'MoneyValue Zero (int)'   => ['fieldMoneyValue', 0, '0', '0.0000'],
            'MoneyValue Zero (float)' => ['fieldMoneyValue', 0.0, '0', '0.0000'],
            'Currency NULL'           => ['fieldCurrency', null, null, null],
            'Currency Empty'          => ['fieldCurrency', '', '', '']
        ];
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
