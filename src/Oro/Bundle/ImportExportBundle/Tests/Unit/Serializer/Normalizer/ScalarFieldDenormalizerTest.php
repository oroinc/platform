<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ScalarFieldDenormalizer;

class ScalarFieldDenormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ScalarFieldDenormalizer */
    private $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new ScalarFieldDenormalizer();
    }

    /**
     * @dataProvider supportsDenormalizationProvider
     */
    public function testSupportsDenormalization(
        bool $isSupported,
        mixed $data,
        string $type,
        array $context = [],
        string $format = null
    ) {
        $this->assertEquals(
            $isSupported,
            $this->denormalizer->supportsDenormalization($data, $type, $format, $context)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function supportsDenormalizationProvider(): array
    {
        return [
            'Null value is not supported' => [
                'isSupported' => false,
                'data' => null,
                'type' => 'integer',
                'context' => [
                    'field' => 'test',
                    'className' => \stdClass::class,
                ],
            ],
            'Object value is not supported' => [
                'isSupported' => false,
                'data' => new \stdClass(),
                'type' => 'integer',
                'context' => [
                    'field' => 'test',
                    'className' => \stdClass::class,
                ],
            ],
            'Array value is not supported' => [
                'isSupported' => false,
                'data' => [],
                'type' => 'integer',
                'context' => [
                    'field' => 'test',
                    'className' => \stdClass::class,
                ],
            ],
            'String to integer is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                ],
            ],
            'String to smallint is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'smallint',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                ],
            ],
            'String to bigint is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'bigint',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                ],
            ],
            'String to float is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'float',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                ],
            ],
            'String to decimal is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'decimal',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                ],
            ],
            'String to money is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'money',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                ],
            ],
            'String to percent is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'percent',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                ],
            ],
            'String to boolean is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'boolean',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                ],
            ],
        ];
    }

    public function testSupportsDenormalizationOnIgnoredField()
    {
        $this->denormalizer->addFieldToIgnore(\stdClass::class, 'test');

        $this->assertEquals(
            false,
            $this->denormalizer->supportsDenormalization(
                '1',
                'decimal',
                null,
                [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                ]
            )
        );
    }

    /**
     * @dataProvider denormalizeProvider
     */
    public function testDenormalize(
        mixed $expectedValue,
        mixed $data,
        string $type,
        array $context = [],
        string $format = null
    ) {
        $this->assertEquals(
            $expectedValue,
            $this->denormalizer->denormalize($data, $type, $format, $context)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function denormalizeProvider(): array
    {
        $biggerThanIntMax = str_repeat('9', (strlen(PHP_INT_MAX) +1));
        $biggerThanIntMaxInScientificValue = '9e' . (strlen(PHP_INT_MAX) + 1);

        $biggerThanFloatMax = str_repeat('9', (strlen(PHP_INT_MAX) * 100));
        $biggerThanFloatMaxInScientificValue = '9e' . (strlen(PHP_INT_MAX) * 100);

        return [
            'String with empty string to int' => [
                'expectedValue' => '',
                'data' => '',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true
                ],
            ],
            'String with 0 in string to int' => [
                'expectedValue' => 0,
                'data' => '0',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true
                ],
            ],
            'String with bigger than int max value in string to int' => [
                'expectedValue' => $biggerThanIntMax,
                'data' => $biggerThanIntMax,
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true
                ],
            ],
            'String with bigger than int max value scientific value in string to int' => [
                'expectedValue' => $biggerThanIntMaxInScientificValue,
                'data' => $biggerThanIntMaxInScientificValue,
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true
                ],
            ],
            'String with empty string to float' => [
                'expectedValue' => '',
                'data' => '',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true
                ],
            ],
            'String with valid string value to int' => [
                'expectedValue' => 1,
                'data' => '1',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true
                ],
            ],
            'String with invalid string value to int and invalid not skipped' => [
                'expectedValue' => 0,
                'data' => 'test',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class
                ],
            ],
            'String with invalid string value to int' => [
                'expectedValue' => 'test',
                'data' => 'test',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true,
                ],
            ],
            'String with valid scientific string value to int' => [
                'expectedValue' => 100,
                'data' => '1e2',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true
                ],
            ],
            'String with valid string value to decimal' => [
                'expectedValue' => 1.2,
                'data' => '1.2',
                'type' => 'decimal',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true
                ],
            ],
            'String with valid scientific string value to decimal' => [
                'expectedValue' => 0.11,
                'data' => '11e-2',
                'type' => 'decimal',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true
                ],
            ],
            'String with bigger than max float in string to decimal' => [
                'expectedValue' => $biggerThanFloatMax,
                'data' => $biggerThanFloatMax,
                'type' => 'decimal',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true
                ],
            ],
            'String with bigger than max float in scientific notation in string to decimal' => [
                'expectedValue' => $biggerThanFloatMaxInScientificValue,
                'data' => $biggerThanFloatMaxInScientificValue,
                'type' => 'decimal',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true
                ],
            ],
            'String with invalid string value to decimal and invalid value is not skipped' => [
                'expectedValue' => 0,
                'data' => 'test',
                'type' => 'decimal',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                ],
            ],
            'String with invalid string value to decimal' => [
                'expectedValue' => 'test',
                'data' => 'test',
                'type' => 'decimal',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true,
                ],
            ],
            'String value 1 to boolean' => [
                'expectedValue' => true,
                'data' => '1',
                'type' => 'boolean',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true,
                ],
            ],
            'String value true to boolean' => [
                'expectedValue' => true,
                'data' => 'true',
                'type' => 'boolean',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true,
                ],
            ],
            'String value on to boolean' => [
                'expectedValue' => true,
                'data' => 'on',
                'type' => 'boolean',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true,
                ],
            ],
            'String value yes to boolean' => [
                'expectedValue' => true,
                'data' => 'yes',
                'type' => 'boolean',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true,
                ],
            ],
            'Int value as string to boolean for BC' => [
                'expectedValue' => true,
                'data' => '2',
                'type' => 'boolean',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true,
                ],
            ],
            'String value 0 to boolean' => [
                'expectedValue' => false,
                'data' => '0',
                'type' => 'boolean',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true,
                ],
            ],
            'String value false to boolean' => [
                'expectedValue' => false,
                'data' => 'false',
                'type' => 'boolean',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true,
                ],
            ],
            'String value off to boolean' => [
                'expectedValue' => false,
                'data' => 'off',
                'type' => 'boolean',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true,
                ],
            ],
            'String value no to boolean' => [
                'expectedValue' => false,
                'data' => 'no',
                'type' => 'boolean',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true,
                ],
            ],
            'String value empty to boolean' => [
                'expectedValue' => false,
                'data' => '',
                'type' => 'boolean',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true,
                ],
            ],
            'String value null to boolean' => [
                'expectedValue' => false,
                'data' => null,
                'type' => 'boolean',
                'context' => [
                    'fieldName' => 'test',
                    'className' => \stdClass::class,
                    'skip_invalid_value' => true,
                ],
            ],
        ];
    }
}
