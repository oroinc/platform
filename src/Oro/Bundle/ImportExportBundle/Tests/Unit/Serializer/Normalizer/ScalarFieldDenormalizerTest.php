<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ScalarFieldDenormalizer;

class ScalarFieldDenormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ScalarFieldDenormalizer */
    protected $denormalizer;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->denormalizer = new ScalarFieldDenormalizer();
    }

    /**
     * @dataProvider supportsDenormalizationProvider
     *
     * @param bool   $isSupported
     * @param mixed  $data
     * @param string $type
     * @param array  $context
     * @param string $format
     */
    public function testSupportsDenormalization(bool $isSupported, $data, $type, array $context = [], $format = null)
    {
        $this->assertEquals(
            $isSupported,
            $this->denormalizer->supportsDenormalization($data, $type, $format, $context)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
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
                    'className' => '\StdClass',
                ],
            ],
            'Object value is not supported' => [
                'isSupported' => false,
                'data' => new \StdClass(),
                'type' => 'integer',
                'context' => [
                    'field' => 'test',
                    'className' => '\StdClass',
                ],
            ],
            'Array value is not supported' => [
                'isSupported' => false,
                'data' => [],
                'type' => 'integer',
                'context' => [
                    'field' => 'test',
                    'className' => '\StdClass',
                ],
            ],
            'String to integer is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                ],
            ],
            'String to smallint is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'smallint',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                ],
            ],
            'String to bigint is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'bigint',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                ],
            ],
            'String to float is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'float',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                ],
            ],
            'String to decimal is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'decimal',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                ],
            ],
            'String to money is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'money',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                ],
            ],
            'String to percent is supported' => [
                'isSupported' => true,
                'data' => '1',
                'type' => 'percent',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                ],
            ],
        ];
    }

    public function testSupportsDenormalizationOnIgnoredField()
    {
        $this->denormalizer->addFieldToIgnore('\StdClass', 'test');

        $this->assertEquals(
            false,
            $this->denormalizer->supportsDenormalization(
                '1',
                'decimal',
                null,
                [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                ]
            )
        );
    }

    /**
     * @dataProvider denormalizeProvider
     *
     * @param mixed  $expectedValue
     * @param mixed  $data
     * @param string $type
     * @param array  $context
     * @param string $format
     */
    public function testDenormalize($expectedValue, $data, $type, array $context = [], $format = null)
    {
        $this->assertEquals(
            $expectedValue,
            $this->denormalizer->denormalize($data, $type, $format, $context)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
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
                    'className' => '\StdClass',
                    'skip_invalid_value' => true
                ],
            ],
            'String with 0 in string to int' => [
                'expectedValue' => 0,
                'data' => '0',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                    'skip_invalid_value' => true
                ],
            ],
            'String with bigger than int max value in string to int' => [
                'expectedValue' => $biggerThanIntMax,
                'data' => $biggerThanIntMax,
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                    'skip_invalid_value' => true
                ],
            ],
            'String with bigger than int max value scientific value in string to int' => [
                'expectedValue' => $biggerThanIntMaxInScientificValue,
                'data' => $biggerThanIntMaxInScientificValue,
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                    'skip_invalid_value' => true
                ],
            ],
            'String with empty string to float' => [
                'expectedValue' => '',
                'data' => '',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                    'skip_invalid_value' => true
                ],
            ],
            'String with valid string value to int' => [
                'expectedValue' => 1,
                'data' => '1',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                    'skip_invalid_value' => true
                ],
            ],
            'String with invalid string value to int and invalid not skipped' => [
                'expectedValue' => 0,
                'data' => 'test',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass'
                ],
            ],
            'String with invalid string value to int' => [
                'expectedValue' => 'test',
                'data' => 'test',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                    'skip_invalid_value' => true,
                ],
            ],
            'String with valid scientific string value to int' => [
                'expectedValue' => 100,
                'data' => '1e2',
                'type' => 'integer',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                    'skip_invalid_value' => true
                ],
            ],
            'String with valid string value to decimal' => [
                'expectedValue' => 1.2,
                'data' => '1.2',
                'type' => 'decimal',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                    'skip_invalid_value' => true
                ],
            ],
            'String with valid scientific string value to decimal' => [
                'expectedValue' => 0.11,
                'data' => '11e-2',
                'type' => 'decimal',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                    'skip_invalid_value' => true
                ],
            ],
            'String with bigger than max float in string to decimal' => [
                'expectedValue' => $biggerThanFloatMax,
                'data' => $biggerThanFloatMax,
                'type' => 'decimal',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                    'skip_invalid_value' => true
                ],
            ],
            'String with bigger than max float in scientific notation in string to decimal' => [
                'expectedValue' => $biggerThanFloatMaxInScientificValue,
                'data' => $biggerThanFloatMaxInScientificValue,
                'type' => 'decimal',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                    'skip_invalid_value' => true
                ],
            ],
            'String with invalid string value to decimal and invalid value is not skipped' => [
                'expectedValue' => 0,
                'data' => 'test',
                'type' => 'decimal',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                ],
            ],
            'String with invalid string value to decimal' => [
                'expectedValue' => 'test',
                'data' => 'test',
                'type' => 'decimal',
                'context' => [
                    'fieldName' => 'test',
                    'className' => '\StdClass',
                    'skip_invalid_value' => true,
                ],
            ],
        ];
    }
}
