<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\ImportExport\Serializer;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\ImportExport\Serializer\EnumNormalizer;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

class EnumNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldHelper;

    /** @var EnumNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        $this->normalizer = new EnumNormalizer($this->fieldHelper);
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization(mixed $value, bool $expected)
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
            [new TestEnumValue(uniqid(), uniqid()), true]
        ];
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization(mixed $value, bool $expected)
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
            [new TestEnumValue(uniqid(), uniqid()), true]
        ];
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(mixed $value, ?array $expected, array $context = [], string $identityField = 'name')
    {
        $type = is_object($value) ? get_class($value) : gettype($value);

        $this->fieldHelper->expects($this->any())
            ->method('getConfigValue')
            ->with($type, 'name', 'identity')
            ->willReturn($identityField === 'name');

        $this->assertEquals($expected, $this->normalizer->normalize($value, $type, $context));
    }

    public function normalizeDataProvider(): array
    {
        $id = uniqid();

        return [
            [null, null],
            [false, null],
            [true, null],
            [[], null],
            [new \stdClass(), null],
            [new TestEnumValue($id, 'name'), ['id' => $id, 'name' => 'name', 'priority' => 0, 'is_default' => false]],
            [
                new TestEnumValue($id, 'name', 100, true),
                ['id' => $id, 'name' => 'name', 'priority' => 100, 'is_default' => true]
            ],
            [
                new TestEnumValue($id, 'name', 100, true),
                ['id' => $id, 'name' => 'name', 'priority' => 100, 'is_default' => true],
                ['mode' => 'full']
            ],
            [
                new TestEnumValue('0', '0', 100, true),
                ['id' => '0', 'name' => '0', 'priority' => 100, 'is_default' => true],
                ['mode' => 'full']
            ],
            [
                new TestEnumValue($id, 'name', 100, true),
                ['name' => 'name'],
                ['mode' => 'short']
            ],
            [
                new TestEnumValue($id, 'name', 100, true),
                ['id' => $id],
                ['mode' => 'short'],
                'id'
            ],
            [
                new TestEnumValue('0', '0', 100, true),
                ['id' => '0'],
                ['mode' => 'short'],
                'id'
            ],
        ];
    }

    /**
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize(array $data, AbstractEnumValue $expected)
    {
        $class = get_class($expected);

        $this->assertEquals($expected, $this->normalizer->denormalize($data, $class));
    }

    public function denormalizeDataProvider(): array
    {
        $id = uniqid();

        return [
            [['id' => $id, 'name' => 'name', 'priority' => 0, 'default' => false], new TestEnumValue($id, 'name')],
            [
                ['id' => $id, 'name' => 'name', 'priority' => 100, 'default' => true],
                new TestEnumValue($id, 'name', 100, true)
            ],
            [
                ['id' => $id, 'name' => 'name', 'priority' => 100, 'default' => true],
                new TestEnumValue($id, 'name', 100, true)
            ],
            'Check that id with "0" value is handled correctly' => [
                ['id' => '0', 'name' => 'name', 'priority' => 100, 'default' => true],
                new TestEnumValue('0', 'name', 100, true)
            ],
        ];
    }
}
