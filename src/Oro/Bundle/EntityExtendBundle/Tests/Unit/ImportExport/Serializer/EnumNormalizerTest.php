<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\ImportExport\Serializer;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\ImportExport\Serializer\EnumNormalizer;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnumNormalizerTest extends TestCase
{
    private FieldHelper&MockObject $fieldHelper;
    private EnumOptionsProvider&MockObject $enumOptionsProvider;
    private EnumNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->enumOptionsProvider = $this->createMock(EnumOptionsProvider::class);

        $this->normalizer = new EnumNormalizer($this->fieldHelper, $this->enumOptionsProvider);
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization(mixed $value, bool $expected): void
    {
        $this->assertEquals($expected, $this->normalizer->supportsNormalization($value));
    }

    public function supportsNormalizationDataProvider(): array
    {
        return [
            [null, false],
            [false, false],
            [true, false],
            [[], false],
            [new \stdClass(), false],
            [new TestEnumValue('test_enum_code', 'Test', uniqid(), 1), true]
        ];
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization(mixed $value, bool $expected): void
    {
        $type = is_object($value) ? get_class($value) : gettype($value);

        $this->assertEquals($expected, $this->normalizer->supportsDenormalization($value, $type));
    }

    public function supportsDenormalizationDataProvider(): array
    {
        return [
            [null, false],
            [false, false],
            [true, false],
            [[], false],
            [new \stdClass(), false],
            [new TestEnumValue('test_enum_code', 'Test', uniqid(), 1), true]
        ];
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(
        mixed $value,
        ?array $expected,
        array $context = [],
        string $extendScope = 'System'
    ): void {
        $type = is_object($value) ? get_class($value) : gettype($value);

        $this->fieldHelper->expects($this->any())
            ->method('getExtendConfigOwner')
            ->with('entityName', 'fieldName')
            ->willReturn($extendScope);

        $this->assertEquals($expected, $this->normalizer->normalize($value, $type, $context));
    }

    public function normalizeDataProvider(): array
    {
        $enumCode = 'test_enum_code';
        $internalId = 'internalId';
        $id = ExtendHelper::buildEnumOptionId($enumCode, $internalId);

        return [
            [null, null],
            [false, null],
            [true, null],
            [[], null],
            [new \stdClass(), null],
            [
                new TestEnumValue($enumCode, 'name', $internalId),
                [
                    'id' => $id,
                    'enumCode' => $enumCode,
                    'name' => 'name',
                    'internalId' => $internalId,
                    'priority' => 0,
                    'is_default' => false
                ]
            ],
            [
                new TestEnumValue($enumCode, 'name', $internalId, 100, true),
                [
                    'id' => $id,
                    'enumCode' => $enumCode,
                    'name' => 'name',
                    'internalId' => $internalId,
                    'priority' => 100,
                    'is_default' => true
                ]
            ],
            [
                new TestEnumValue($enumCode, 'name', $internalId, 100, true),
                [
                    'id' => $id,
                    'enumCode' => $enumCode,
                    'name' => 'name',
                    'internalId' => $internalId,
                    'priority' => 100,
                    'is_default' => true
                ],
                ['mode' => 'full']
            ],
            [
                new TestEnumValue($enumCode, 'test', '0', 100, true),
                [
                    'id' => 'test_enum_code.0',
                    'enumCode' => 'test_enum_code',
                    'name' => 'test',
                    'internalId' => '0',
                    'priority' => 100,
                    'is_default' => true
                ],
                ['mode' => 'full']
            ],
            [
                new TestEnumValue($enumCode, 'name', 'name', 100, true),
                ['name' => 'name'],
                [
                    'mode' => 'short',
                    'entityName' => 'entityName',
                    'fieldName' => 'fieldName'
                ],
                'Custom'
            ],
            [
                new TestEnumValue($enumCode, 'Test', 'name', 100, true),
                ['id' => 'name'],
                [
                    'mode' => 'short',
                    'entityName' => 'entityName',
                    'fieldName' => 'fieldName'
                ],
            ],
            [
                new TestEnumValue($enumCode, 'Test', '0', 100, true),
                ['id' => '0'],
                [
                    'mode' => 'short',
                    'entityName' => 'entityName',
                    'fieldName' => 'fieldName'
                ],
            ],
        ];
    }

    /**
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize(array $data, EnumOptionInterface $expected): void
    {
        $class = get_class($expected);
        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumChoicesByCode')
            ->with($class)
            ->willReturn(['Option 1' => 'option_1', 'Option 2' => 'option_2']);

        $this->assertEquals($expected, $this->normalizer->denormalize($data, $class));
    }

    public function denormalizeDataProvider(): array
    {
        return [
            [
                [
                    'enumCode' => 'test_enum_code',
                    'name' => 'Option 1',
                    'internalId' => 'test1',
                    'priority' => 0,
                    'default' => false
                ],
                new TestEnumValue('test_enum_code', 'Option 1', 'test1')
            ],
            [
                [
                    'enumCode' => 'test_enum_code',
                    'name' => 'Option 1',
                    'internalId' => 'test1',
                    'priority' => 100,
                    'default' => true
                ],
                new TestEnumValue('test_enum_code', 'Option 1', 'test1', 100, true)
            ],
            [
                [
                    'enumCode' => 'test_enum_code',
                    'name' => 'Option 1',
                    'internalId' => 'test1',
                    'priority' => 100,
                    'default' => true
                ],
                new TestEnumValue('test_enum_code', 'Option 1', 'test1', 100, true)
            ],
            'Check that id with "0" value is handled correctly' => [
                [
                    'enumCode' => 'test_enum_code',
                    'name' => 'Option 1',
                    'internalId' => '0',
                    'priority' => 100,
                    'default' => true
                ],
                new TestEnumValue('test_enum_code', 'Option 1', '0', 100, true)
            ],
            'Check that a translated value could be transformed back to key value' => [
                ['enumCode' => '', 'name' => 'Option 1', 'internalId' => '', 'priority' => 100, 'default' => true],
                new TestEnumValue('', 'Option 1', '', 100, true)
            ],
            'Check with an unknown enum value' => [
                ['enumCode' => 'test_enum_code',
                    'name' => 'Option 3',
                    'internalId' => '',
                    'priority' => 100,
                    'default' => true],
                new TestEnumValue('test_enum_code', 'Option 3', '', 100, true)
            ],
            'Check without name value' => [
                ['enumCode' => 'test_enum_code', 'internalId' => 'test1', 'priority' => 100, 'default' => true],
                new TestEnumValue('test_enum_code', '', 'test1', 100, true)
            ],
        ];
    }
}
