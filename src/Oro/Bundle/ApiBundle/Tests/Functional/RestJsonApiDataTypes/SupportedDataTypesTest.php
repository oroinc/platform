<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiDataTypes;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestAllDataTypes;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SupportedDataTypesTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/supported_data_types.yml'
        ]);
    }

    private function isPostgreSql(): bool
    {
        return $this->getEntityManager()->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform;
    }

    private function getEntityData(int $entityId): array
    {
        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $entityId);

        $fieldPercent100 = $entity->fieldPercent100;
        if (null !== $fieldPercent100) {
            $fieldPercent100 /= 100.0;
        }
        $fieldPercent100Round = $entity->fieldPercent100Round;
        if (null !== $fieldPercent100Round) {
            $fieldPercent100Round /= 100.0;
        }
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
            'fieldString'          => $entity->fieldString,
            'fieldText'            => $entity->fieldText,
            'fieldInt'             => $entity->fieldInt,
            'fieldSmallInt'        => $entity->fieldSmallInt,
            'fieldBigInt'          => $entity->fieldBigInt,
            'fieldBoolean'         => $entity->fieldBoolean,
            'fieldDecimal'         => $entity->fieldDecimal,
            'fieldDecimalDefault'  => $entity->fieldDecimalDefault,
            'fieldFloat'           => $entity->fieldFloat,
            'fieldArray'           => $entity->fieldArray,
            'fieldSimpleArray'     => $entity->fieldSimpleArray,
            'fieldJsonArray'       => $entity->fieldJsonArray,
            'fieldJson'            => $entity->fieldJson,
            'fieldDateTime'        => $fieldDateTime,
            'fieldDate'            => $fieldDate,
            'fieldTime'            => $fieldTime,
            'fieldGuid'            => $entity->fieldGuid,
            'fieldPercent'         => $entity->fieldPercent,
            'fieldPercent100'      => $fieldPercent100,
            'fieldPercentRound'    => $entity->fieldPercentRound,
            'fieldPercent100Round' => $fieldPercent100Round,
            'fieldMoney'           => $entity->fieldMoney,
            'fieldDuration'        => $entity->fieldDuration,
            'fieldMoneyValue'      => $entity->fieldMoneyValue,
            'fieldCurrency'        => $entity->fieldCurrency
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
                        'fieldString'          => 'Test String 1 Value',
                        'fieldText'            => 'Test Text 1 Value',
                        'fieldInt'             => 1,
                        'fieldSmallInt'        => 1,
                        'fieldBigInt'          => '123456789012345',
                        'fieldBoolean'         => false,
                        'fieldDecimal'         => '1.234567',
                        'fieldDecimalDefault'  => '123',
                        'fieldFloat'           => 1.1,
                        'fieldArray'           => [1, 2, 3],
                        'fieldSimpleArray'     => ['1', '2', '3'],
                        'fieldJsonArray'       => ['key1' => 'value1'],
                        'fieldJson'            => ['key1' => 'value1'],
                        'fieldDateTime'        => '2010-10-01T10:11:12Z',
                        'fieldDate'            => '2010-10-01',
                        'fieldTime'            => '10:11:12',
                        'fieldGuid'            => 'ae404bc5-c9bb-4677-9bad-21144c704734',
                        'fieldPercent'         => 0.1,
                        'fieldPercent100'      => 0.1,
                        'fieldPercentRound'    => 0.1,
                        'fieldPercent100Round' => 0.1,
                        'fieldMoney'           => '1.2340',
                        'fieldDuration'        => 11,
                        'fieldMoneyValue'      => '1.2345',
                        'fieldCurrency'        => 'USD'
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
                    'fieldString'          => 'New String',
                    'fieldText'            => 'Some Text',
                    'fieldInt'             => 2147483647,
                    'fieldSmallInt'        => 32767,
                    'fieldBigInt'          => '9223372036854775807',
                    'fieldBoolean'         => true,
                    'fieldDecimal'         => '123.456789',
                    'fieldDecimalDefault'  => '123456',
                    'fieldFloat'           => 123.456789,
                    'fieldArray'           => [1, 2, 3],
                    'fieldSimpleArray'     => ['1', '2', '3'],
                    'fieldJsonArray'       => ['key' => 'value'],
                    'fieldJson'            => ['key' => 'value'],
                    'fieldDateTime'        => '2017-01-21T10:20:30Z',
                    'fieldDate'            => '2017-01-21',
                    'fieldTime'            => '10:20:30',
                    'fieldGuid'            => '6f690d83-9b60-4da4-9c47-1b163229db6d',
                    'fieldPercent'         => 0.1234567,
                    'fieldPercent100'      => 0.1234567,
                    'fieldPercentRound'    => 0.1234567,
                    'fieldPercent100Round' => 0.1234567,
                    'fieldMoney'           => '123.456',
                    'fieldDuration'        => 123,
                    'fieldMoneyValue'      => '123.4567',
                    'fieldCurrency'        => 'USD'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        $expectedData['data']['attributes']['fieldPercentRound'] = 0.124;
        $expectedData['data']['attributes']['fieldPercent100Round'] = 0.12346;
        $expectedData['data']['attributes']['fieldMoney'] .= '0';
        $this->assertResponseContains($expectedData, $response);

        $expectedEntityData = $data['data']['attributes'];
        $expectedEntityData['fieldDateTime'] = str_replace('Z', '+0000', $expectedEntityData['fieldDateTime']);
        $expectedEntityData['fieldDecimal'] .= '00';
        $expectedEntityData['fieldPercentRound'] = 0.124;
        $expectedEntityData['fieldPercent100Round'] = 0.12346;
        $expectedEntityData['fieldMoney'] .= '0';
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
                'fieldString'         => null,
                'fieldText'           => null,
                'fieldInt'            => null,
                'fieldSmallInt'       => null,
                'fieldBigInt'         => null,
                'fieldBoolean'        => $fieldBooleanNullValue,
                'fieldDecimal'        => null,
                'fieldDecimalDefault' => null,
                'fieldFloat'          => null,
                'fieldArray'          => null,
                'fieldSimpleArray'    => [],
                'fieldJsonArray'      => [],
                'fieldJson'           => null,
                'fieldDateTime'       => null,
                'fieldDate'           => null,
                'fieldTime'           => null,
                'fieldGuid'           => null,
                'fieldPercent'        => null,
                'fieldPercent100'     => null,
                'fieldMoney'          => null,
                'fieldDuration'       => null,
                'fieldMoneyValue'     => null,
                'fieldCurrency'       => null
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
                    'fieldString'          => 'New String',
                    'fieldText'            => 'Some Text',
                    'fieldInt'             => -2147483648,
                    'fieldSmallInt'        => -32768,
                    'fieldBigInt'          => '-9223372036854775808',
                    'fieldBoolean'         => true,
                    'fieldDecimal'         => '123.456789',
                    'fieldDecimalDefault'  => '123456',
                    'fieldFloat'           => 123.456789,
                    'fieldArray'           => [1, 2, 3],
                    'fieldSimpleArray'     => ['1', '2', '3'],
                    'fieldJsonArray'       => ['key' => 'value'],
                    'fieldJson'            => ['key' => 'value'],
                    'fieldDateTime'        => '2017-01-21T10:20:30Z',
                    'fieldDate'            => '2017-01-21',
                    'fieldTime'            => '10:20:30',
                    'fieldGuid'            => '6f690d83-9b60-4da4-9c47-1b163229db6d',
                    'fieldPercent'         => 0.1234567,
                    'fieldPercent100'      => 0.1234567,
                    'fieldPercentRound'    => 0.1234567,
                    'fieldPercent100Round' => 0.1234567,
                    'fieldMoney'           => '123.456',
                    'fieldDuration'        => 123,
                    'fieldMoneyValue'      => '123.4567',
                    'fieldCurrency'        => 'UAH'
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data);

        $expectedData = $data;
        $expectedData['data']['attributes']['fieldPercentRound'] = 0.124;
        $expectedData['data']['attributes']['fieldPercent100Round'] = 0.12346;
        $expectedData['data']['attributes']['fieldMoney'] .= '0';
        $this->assertResponseContains($expectedData, $response);

        $expectedEntityData = $data['data']['attributes'];
        $expectedEntityData['fieldDateTime'] = str_replace('Z', '+0000', $expectedEntityData['fieldDateTime']);
        $expectedEntityData['fieldDecimal'] .= '00';
        $expectedEntityData['fieldPercentRound'] = 0.124;
        $expectedEntityData['fieldPercent100Round'] = 0.12346;
        $expectedEntityData['fieldMoney'] .= '0';
        self::assertArrayContains(
            $expectedEntityData,
            $this->getEntityData((int)$this->getResourceId($response))
        );
    }

    /**
     * @dataProvider emptyValueDataProvider
     */
    public function testCreateShouldAcceptEmptyValue(
        string $fieldName,
        mixed $value,
        mixed $responseValue,
        mixed $entityValue
    ) {
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
     * @dataProvider emptyValueDataProvider
     */
    public function testUpdateShouldAcceptEmptyValue(
        string $fieldName,
        mixed $value,
        mixed $responseValue,
        mixed $entityValue
    ) {
        // this is a workaround for a known PDO driver issue not saving null to nullable boolean field
        // for PostgreSQL, see https://github.com/doctrine/dbal/issues/2580 for details
        if ('fieldBoolean' === $fieldName && $this->isPostgreSql()) {
            if (null === $responseValue) {
                $responseValue = false;
            }
            if (null === $entityValue) {
                $entityValue = false;
            }
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

    public function emptyValueDataProvider(): array
    {
        return [
            'String NULL'                  => ['fieldString', null, null, null],
            'String Empty'                 => ['fieldString', '', '', ''],
            'Text NULL'                    => ['fieldText', null, null, null],
            'Text Empty'                   => ['fieldText', '', '', ''],
            'Int NULL'                     => ['fieldInt', null, null, null],
            'Int Zero'                     => ['fieldInt', 0, 0, 0],
            'Int Zero (string)'            => ['fieldInt', '0', 0, 0],
            'SmallInt NULL'                => ['fieldSmallInt', null, null, null],
            'SmallInt Zero'                => ['fieldSmallInt', 0, 0, 0],
            'SmallInt Zero (string)'       => ['fieldSmallInt', '0', 0, 0],
            'BigInt NULL'                  => ['fieldBigInt', null, null, null],
            'BigInt Zero'                  => ['fieldBigInt', '0', '0', '0'],
            'BigInt Zero (int)'            => ['fieldBigInt', 0, '0', '0'],
            'Boolean NULL'                 => ['fieldBoolean', null, null, null],
            'Boolean FALSE'                => ['fieldBoolean', false, false, false],
            'Decimal NULL'                 => ['fieldDecimal', null, null, null],
            'Decimal Zero'                 => ['fieldDecimal', '0', '0', '0.00000000'],
            'Decimal Zero (int)'           => ['fieldDecimal', 0, '0', '0.00000000'],
            'Decimal Zero (float)'         => ['fieldDecimal', 0.0, '0', '0.00000000'],
            'Default Decimal NULL'         => ['fieldDecimalDefault', null, null, null],
            'Default Decimal Zero'         => ['fieldDecimalDefault', '0', '0', '0'],
            'Default Decimal Zero (int)'   => ['fieldDecimalDefault', 0, '0', '0'],
            'Default Decimal Zero (float)' => ['fieldDecimalDefault', 0.0, '0', '0'],
            'Float NULL'                   => ['fieldFloat', null, null, null],
            'Float Zero'                   => ['fieldFloat', 0.0, 0, 0.0],
            'Float Zero (string)'          => ['fieldFloat', '0', 0, 0.0],
            'Float Zero (int)'             => ['fieldFloat', 0, 0, 0.0],
            'Array NULL'                   => ['fieldArray', null, null, null],
            'Array Empty'                  => ['fieldArray', [], [], []],
            'SimpleArray NULL'             => ['fieldSimpleArray', null, null, []],
            'SimpleArray Empty'            => ['fieldSimpleArray', [], null, []],
            'JsonArray NULL'               => ['fieldJsonArray', null, null, []],
            'JsonArray Empty'              => ['fieldJsonArray', [], null, []],
            'Json NULL'                    => ['fieldJson', null, null, null],
            'Json Empty'                   => ['fieldJson', [], [], []],
            'DateTime NULL'                => ['fieldDateTime', null, null, null],
            'Date NULL'                    => ['fieldDate', null, null, null],
            'Time NULL'                    => ['fieldTime', null, null, null],
            'Guid NULL'                    => ['fieldGuid', null, null, null],
            'Percent NULL'                 => ['fieldPercent', null, null, null],
            'Percent Zero'                 => ['fieldPercent', 0.0, 0, 0.0],
            'Percent Zero (string)'        => ['fieldPercent', '0', 0, 0.0],
            'Percent Zero (int)'           => ['fieldPercent', 0, 0, 0.0],
            'Percent_100 NULL'             => ['fieldPercent100', null, null, null],
            'Percent_100 Zero'             => ['fieldPercent100', 0.0, 0, 0.0],
            'Percent_100 Zero (string)'    => ['fieldPercent100', '0', 0, 0.0],
            'Percent_100 Zero (int)'       => ['fieldPercent100', 0, 0, 0.0],
            'Money NULL'                   => ['fieldMoney', null, null, null],
            'Money Zero'                   => ['fieldMoney', '0', '0.0000', '0.0000'],
            'Money Zero (int)'             => ['fieldMoney', 0, '0.0000', '0.0000'],
            'Money Zero (float)'           => ['fieldMoney', 0.0, '0.0000', '0.0000'],
            'Duration NULL'                => ['fieldDuration', null, null, null],
            'Duration Empty'               => ['fieldDuration', '', null, null],
            'Duration Zero'                => ['fieldDuration', 0, 0, 0],
            'Duration Zero (string)'       => ['fieldDuration', '0', 0, 0],
            'MoneyValue NULL'              => ['fieldMoneyValue', null, null, null],
            'MoneyValue Zero'              => ['fieldMoneyValue', '0', '0.0000', '0.0000'],
            'MoneyValue Zero (int)'        => ['fieldMoneyValue', 0, '0.0000', '0.0000'],
            'MoneyValue Zero (float)'      => ['fieldMoneyValue', 0.0, '0.0000', '0.0000'],
            'Currency NULL'                => ['fieldCurrency', null, null, null],
            'Currency Empty'               => ['fieldCurrency', '', '', '']
        ];
    }

    /**
     * @dataProvider invalidValueDataProvider
     */
    public function testUpdateShouldHandleInvalidValue(
        string $fieldName,
        mixed $value,
        string $errorDetail = 'This value is not valid.'
    ) {
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

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'form constraint',
                'detail' => $errorDetail,
                'source' => ['pointer' => '/data/attributes/' . $fieldName]
            ],
            $response
        );
    }

    public function invalidValueDataProvider(): array
    {
        return [
            'Int (empty string)'                  => ['fieldInt', ''],
            'Int (not number string)'             => ['fieldInt', 'a'],
            'SmallInt (empty string)'             => ['fieldSmallInt', ''],
            'SmallInt (not number string)'        => ['fieldSmallInt', 'a'],
            'BigInt (empty string)'               => ['fieldBigInt', ''],
            'BigInt (not number string)'          => ['fieldBigInt', 'a'],
            'Boolean (empty string)'              => ['fieldBoolean', ''],
            'Boolean (string)'                    => ['fieldBoolean', 'a'],
            'Boolean (int, neither 0 nor 1)'      => ['fieldBoolean', 10],
            'Decimal (empty string)'              => ['fieldDecimal', ''],
            'Decimal (not number string)'         => ['fieldDecimal', 'a'],
            'Default Decimal (empty string)'      => ['fieldDecimalDefault', ''],
            'Default Decimal (not number string)' => ['fieldDecimalDefault', 'a'],
            'Float (empty string)'                => ['fieldFloat', ''],
            'Float (not number string)'           => ['fieldFloat', 'a'],
            'Array (empty string)'                => ['fieldArray', ''],
            'Array (not array)'                   => ['fieldArray', 0],
            'SimpleArray (empty string)'          => ['fieldSimpleArray', ''],
            'SimpleArray (not array)'             => ['fieldSimpleArray', 0],
            'JsonArray (empty string)'            => ['fieldJsonArray', ''],
            'JsonArray (not array)'               => ['fieldJsonArray', 0],
            'Json (empty string)'                 => ['fieldJson', ''],
            'Json (not array)'                    => ['fieldJson', 0],
            'DateTime (empty string)'             => ['fieldDateTime', ''],
            'DateTime (invalid string)'           => ['fieldDateTime', 'a', 'The "a" is not valid datetime.'],
            'DateTime (not string)'               => ['fieldDateTime', false, 'The "false" is not valid datetime.'],
            'Date (empty string)'                 => ['fieldDate', ''],
            'Date (invalid string)'               => ['fieldDate', 'a', 'The "a" is not valid date.'],
            'Date (not string)'                   => ['fieldDate', false, 'The "false" is not valid date.'],
            'Time (empty string)'                 => ['fieldTime', ''],
            'Time (invalid string)'               => ['fieldTime', 'a', 'The "a" is not valid time.'],
            'Time (not string)'                   => ['fieldTime', false, 'The "false" is not valid time.'],
            'Percent (empty string)'              => ['fieldPercent', ''],
            'Percent (not number string)'         => ['fieldPercent', 'a'],
            'Percent_100 (empty string)'          => ['fieldPercent100', ''],
            'Percent_100 (not number string)'     => ['fieldPercent100', 'a'],
            'Money (empty string)'                => ['fieldMoney', ''],
            'Money (not number string)'           => ['fieldMoney', 'a'],
            'Duration (not number string)'        => ['fieldDuration', 'a', 'Value is not in a valid duration format'],
            'MoneyValue (empty string)'           => ['fieldMoneyValue', ''],
            'MoneyValue (not number string)'      => ['fieldMoneyValue', 'a']
        ];
    }

    /**
     * @dataProvider validBooleanValueDataProvider
     */
    public function testValidValuesForBooleanField(string|int|bool $submittedValue, bool $expectedValue)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    'fieldBoolean' => $submittedValue
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data);

        $expectedResponseData = $data;
        $expectedResponseData['data']['attributes']['fieldBoolean'] = $expectedValue;
        $this->assertResponseContains($expectedResponseData, $response);

        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getResourceId($response));
        self::assertSame($expectedValue, $entity->fieldBoolean);
    }

    public function validBooleanValueDataProvider(): array
    {
        return [
            'false'          => [false, false],
            'true'           => [true, true],
            '0'              => [0, false],
            '1'              => [1, true],
            '0 (string)'     => ['0', false],
            '1 (string)'     => ['1', true],
            'true (string)'  => ['false', false],
            'false (string)' => ['true', true],
            'no'             => ['no', false],
            'yes'            => ['yes', true]
        ];
    }

    /**
     * @dataProvider validIntegerValueDataProvider
     */
    public function testValidValuesForIntegerField(int $submittedValue, int $expectedValue)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    'fieldInt' => $submittedValue
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data);

        $expectedResponseData = $data;
        $expectedResponseData['data']['attributes']['fieldInt'] = $expectedValue;
        $this->assertResponseContains($expectedResponseData, $response);

        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getResourceId($response));
        self::assertSame($expectedValue, $entity->fieldInt);
    }

    public function validIntegerValueDataProvider(): array
    {
        return [
            '1'  => [1, 1],
            '-1' => [-1, -1],
        ];
    }

    /**
     * @dataProvider validDateTimeValueDataProvider
     */
    public function testValidValuesForDateTimeField(string $submittedValue, string $responseValue, string $entityValue)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    'fieldDateTime' => $submittedValue
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data);

        $expectedResponseData = $data;
        $expectedResponseData['data']['attributes']['fieldDateTime'] = $responseValue;
        $this->assertResponseContains($expectedResponseData, $response);

        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getResourceId($response));
        self::assertEquals($entityValue, $entity->fieldDateTime->format('Y-m-d\TH:i:s.vO'));
    }

    public function validDateTimeValueDataProvider(): array
    {
        return [
            'year only'                              => [
                '2017',
                '2017-01-01T00:00:00Z',
                '2017-01-01T00:00:00.000+0000'
            ],
            'year and month only'                    => [
                '2017-07',
                '2017-07-01T00:00:00Z',
                '2017-07-01T00:00:00.000+0000'
            ],
            'date only'                              => [
                '2017-07-21',
                '2017-07-21T00:00:00Z',
                '2017-07-21T00:00:00.000+0000'
            ],
            'with timezone'                          => [
                '2017-07-21T10:20:30+05:00',
                '2017-07-21T05:20:30Z',
                '2017-07-21T05:20:30.000+0000'
            ],
            'with UTC timezone'                      => [
                '2017-07-21T10:20:30Z',
                '2017-07-21T10:20:30Z',
                '2017-07-21T10:20:30.000+0000'
            ],
            'without seconds, with timezone'         => [
                '2017-07-21T10:20+05:00',
                '2017-07-21T05:20:00Z',
                '2017-07-21T05:20:00.000+0000'
            ],
            'without seconds, with UTC timezone'     => [
                '2017-07-21T10:20Z',
                '2017-07-21T10:20:00Z',
                '2017-07-21T10:20:00.000+0000'
            ],
            'with milliseconds and timezone'         => [
                '2017-07-21T10:20:30.123+05:00',
                '2017-07-21T05:20:30Z',
                '2017-07-21T05:20:30.000+0000'
            ],
            'with milliseconds and UTC timezone'     => [
                '2017-07-21T10:20:30.123Z',
                '2017-07-21T10:20:30Z',
                '2017-07-21T10:20:30.000+0000'
            ],
            'max time'                               => [
                '2017-07-21T23:59:59Z',
                '2017-07-21T23:59:59Z',
                '2017-07-21T23:59:59.000+0000'
            ],
            'year and time with timezone'            => [
                '2017T10:20:30+05:00',
                '2017-01-01T05:20:30Z',
                '2017-01-01T05:20:30.000+0000'
            ],
            'year, month and time with timezone'     => [
                '2017-07T10:20:30+05:00',
                '2017-07-01T05:20:30Z',
                '2017-07-01T05:20:30.000+0000'
            ],
            'year and time with UTC timezone'        => [
                '2017T10:20:30Z',
                '2017-01-01T10:20:30Z',
                '2017-01-01T10:20:30.000+0000'
            ],
            'year, month and time with UTC timezone' => [
                '2017-07T10:20:30Z',
                '2017-07-01T10:20:30Z',
                '2017-07-01T10:20:30.000+0000'
            ]
        ];
    }

    /**
     * @dataProvider invalidDateTimeDataProvider
     */
    public function testInvalidValuesForDateTimeField(string $value, string $errorDetail = 'This value is not valid.')
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    'fieldDateTime' => $value
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'form constraint',
                'detail' => $errorDetail,
                'source' => ['pointer' => '/data/attributes/fieldDateTime']
            ],
            $response
        );
    }

    public function invalidDateTimeDataProvider(): array
    {
        return [
            'without timezone'                        => [
                '2017-07-21T10:20:30',
                'The "2017-07-21T10:20:30" is not valid datetime.'
            ],
            'without seconds and timezone'            => [
                '2017-07-21T10:20',
                'The "2017-07-21T10:20" is not valid datetime.'
            ],
            'with milliseconds, but without timezone' => [
                '2017-07-21T10:20:30.123',
                'The "2017-07-21T10:20:30.123" is not valid datetime.'
            ],
            'without minutes'                         => [
                '2017-07-21T10Z',
                'The "2017-07-21T10Z" is not valid datetime.'
            ],
            'invalid time delimiter'                  => [
                '2017-07-21 10:20:30',
                'The "2017-07-21 10:20:30" is not valid datetime.'
            ],
            'invalid date'                            => [
                '2017-02-30T10:20:30Z',
                'The "2017-02-30" is not valid date.'
            ],
            'out of bounds for months'                => [
                '2017-13-21T10:20:30Z',
                'The "2017-13-21" is not valid date.'
            ],
            'out of bounds for days'                  => [
                '2017-07-32T10:20:30Z',
                'The "2017-07-32" is not valid date.'
            ],
            'out of bounds for hours'                 => [
                '2017-07-21T24:20:30Z',
                'The "24:20:30" is not valid time.'
            ],
            'out of bounds for minutes'               => [
                '2017-07-21T10:60:30Z',
                'The "10:60:30" is not valid time.'
            ],
            'out of bounds for seconds'               => [
                '2017-07-21T10:20:60Z',
                'The "10:20:60" is not valid time.'
            ],
            'without leading zero in months'          => [
                '2017-7-21T10:20:30Z',
                'The "2017-7-21T10:20:30Z" is not valid datetime.'
            ],
            'without leading zero in days'            => [
                '2017-07-1T10:20:30Z',
                'The "2017-07-1T10:20:30Z" is not valid datetime.'
            ],
            'without leading zero in hours'           => [
                '2017-07-21T1:20:30Z',
                'The "2017-07-21T1:20:30Z" is not valid datetime.'
            ],
            'without leading zero in minutes'         => [
                '2017-07-21T10:1:30Z',
                'The "2017-07-21T10:1:30Z" is not valid datetime.'
            ],
            'without leading zero in seconds'         => [
                '2017-07-21T10:20:1Z',
                'The "2017-07-21T10:20:1Z" is not valid datetime.'
            ]
        ];
    }

    /**
     * @dataProvider validDateValueDataProvider
     */
    public function testValidValuesForDateField(string $submittedValue, string $responseValue, string $entityValue)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    'fieldDate' => $submittedValue
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data);

        $expectedResponseData = $data;
        $expectedResponseData['data']['attributes']['fieldDate'] = $responseValue;
        $this->assertResponseContains($expectedResponseData, $response);

        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getResourceId($response));
        self::assertEquals($entityValue, $entity->fieldDate->format('Y-m-d\TH:i:s.vO'));
    }

    public function validDateValueDataProvider(): array
    {
        return [
            'full date'           => [
                '2017-07-21',
                '2017-07-21',
                '2017-07-21T00:00:00.000+0000'
            ],
            'year only'           => [
                '2017',
                '2017-01-01',
                '2017-01-01T00:00:00.000+0000'
            ],
            'year and month only' => [
                '2017-07',
                '2017-07-01',
                '2017-07-01T00:00:00.000+0000'
            ]
        ];
    }

    /**
     * @dataProvider invalidDateDataProvider
     */
    public function testInvalidValuesForDateField(string $value, string $errorDetail = 'This value is not valid.')
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    'fieldDate' => $value
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'form constraint',
                'detail' => $errorDetail,
                'source' => ['pointer' => '/data/attributes/fieldDate']
            ],
            $response
        );
    }

    public function invalidDateDataProvider(): array
    {
        return [
            'with time'                      => [
                '2017-07-21T00:00:00',
                'The "2017-07-21T00:00:00" is not valid date.'
            ],
            'with time and timezone'         => [
                '2017-07-21T00:00:00+05:00',
                'The "2017-07-21T00:00:00+05:00" is not valid date.'
            ],
            'with time and UTC timezone'     => [
                '2017-07-21T00:00:00Z',
                'The "2017-07-21T00:00:00Z" is not valid date.'
            ],
            'invalid date'                   => ['2017-02-30', 'The "2017-02-30" is not valid date.'],
            'out of bounds for months'       => ['2017-13-21', 'The "2017-13-21" is not valid date.'],
            'out of bounds for days'         => ['2017-07-32', 'The "2017-07-32" is not valid date.'],
            'without leading zero in months' => ['2017-7-21', 'The "2017-7-21" is not valid date.'],
            'without leading zero in days'   => ['2017-07-1', 'The "2017-07-1" is not valid date.']
        ];
    }

    /**
     * @dataProvider validTimeValueDataProvider
     */
    public function testValidValuesForTimeField(string $submittedValue, string $responseValue, string $entityValue)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    'fieldTime' => $submittedValue
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data);

        $expectedResponseData = $data;
        $expectedResponseData['data']['attributes']['fieldTime'] = $responseValue;
        $this->assertResponseContains($expectedResponseData, $response);

        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getResourceId($response));
        self::assertEquals($entityValue, $entity->fieldTime->format('Y-m-d\TH:i:s.vO'));
    }

    public function validTimeValueDataProvider(): array
    {
        return [
            'full time'            => [
                '10:20:30',
                '10:20:30',
                '1970-01-01T10:20:30.000+0000'
            ],
            'without seconds'      => [
                '10:20',
                '10:20:00',
                '1970-01-01T10:20:00.000+0000'
            ],
            'max time'             => [
                '23:59:59',
                '23:59:59',
                '1970-01-01T23:59:59.000+0000'
            ],
            'without leading zero' => [
                '1:2:3',
                '01:02:03',
                '1970-01-01T01:02:03.000+0000'
            ]
        ];
    }

    /**
     * @dataProvider invalidTimeDataProvider
     */
    public function testInvalidValuesForTimeField(string $value, string $errorDetail = 'This value is not valid.')
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    'fieldTime' => $value
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'form constraint',
                'detail' => $errorDetail,
                'source' => ['pointer' => '/data/attributes/fieldTime']
            ],
            $response
        );
    }

    public function invalidTimeDataProvider(): array
    {
        return [
            'with date'                 => ['2017-07-21T10:20:30Z', 'The "2017-07-21T10:20:30Z" is not valid time.'],
            'with timezone'             => ['10:20:30+05:00', 'The "10:20:30+05:00" is not valid time.'],
            'with UTC timezone'         => ['10:20:30Z', 'The "10:20:30Z" is not valid time.'],
            'without minutes'           => ['10', 'The "10" is not valid time.'],
            'out of bounds for hours'   => ['24:20:30', 'The "24:20:30" is not valid time.'],
            'out of bounds for minutes' => ['10:60:30', 'The "10:60:30" is not valid time.'],
            'out of bounds for seconds' => ['10:20:60', 'The "10:20:60" is not valid time.']
        ];
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
     * @dataProvider validDurationValueDataProvider
     */
    public function testValidValuesForDurationField(string|int $submittedValue, int $durationInSeconds)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@TestItem1->id)>',
                'attributes' => [
                    'fieldDuration' => $submittedValue
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@TestItem1->id)>'], $data);

        $expectedResponseData = $data;
        $expectedResponseData['data']['attributes']['fieldDuration'] = $durationInSeconds;
        $this->assertResponseContains($expectedResponseData, $response);

        $entity = $this->getEntityManager()->find(TestAllDataTypes::class, $this->getResourceId($response));
        self::assertSame($durationInSeconds, $entity->fieldDuration);
    }

    public function validDurationValueDataProvider(): array
    {
        return [
            'seconds'                            => [123, 123],
            'seconds (string)'                   => ['123', 123],
            'Jira style'                         => ['1h 2m 3s', 3723],
            'Jira style (hours only)'            => ['1h', 3600],
            'Jira style (minutes only)'          => ['1m', 60],
            'Jira style (seconds only)'          => ['1s', 1],
            'Jira style (minutes and seconds)'   => ['1m 2s', 62],
            'Column style'                       => ['1:2:3', 3723],
            'Column style (minutes and seconds)' => ['1:2', 62]
        ];
    }
}
