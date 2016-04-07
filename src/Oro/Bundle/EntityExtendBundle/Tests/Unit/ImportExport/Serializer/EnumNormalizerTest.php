<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\ImportExport\Serializer;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\ImportExport\Serializer\EnumNormalizer;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;

class EnumNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldHelper;

    /**
     * @var EnumNormalizer
     */
    protected $normalizer;

    protected function setUp()
    {
        $this->fieldHelper = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer = new EnumNormalizer($this->fieldHelper);
    }

    /**
     * @param mixed $value
     * @param bool $expected
     *
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization($value, $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsNormalization($value));
    }

    /**
     * @return array
     */
    public function supportsNormalizationDataProvider()
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
     * @param mixed $value
     * @param bool $expected
     *
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization($value, $expected)
    {
        $type = is_object($value) ? get_class($value) : gettype($value);

        $this->assertEquals($expected, $this->normalizer->supportsDenormalization($value, $type));
    }

    /**
     * @return array
     */
    public function supportsDenormalizationDataProvider()
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
     * @param mixed $value
     * @param bool $expected
     * @param array $context
     * @param string $identityField
     *
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize($value, $expected, array $context = [], $identityField = 'name')
    {
        $type = is_object($value) ? get_class($value) : gettype($value);

        $this->fieldHelper->expects($this->any())
            ->method('getConfigValue')
            ->with($type, 'name', 'identity')
            ->willReturn($identityField === 'name');

        $this->assertEquals($expected, $this->normalizer->normalize($value, $type, $context));
    }

    /**
     * @return array
     */
    public function normalizeDataProvider()
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
                new TestEnumValue($id, 'name', 100, true),
                ['name' => 'name'],
                ['mode' => 'short']
            ],
            [
                new TestEnumValue($id, 'name', 100, true),
                ['id' => $id],
                ['mode' => 'short'],
                'id'
            ]
        ];
    }

    /**
     * @param array $data
     * @param AbstractEnumValue $expected
     *
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize($data, $expected)
    {
        $class = get_class($expected);

        $this->assertEquals($expected, $this->normalizer->denormalize($data, $class));
    }

    /**
     * @return array
     */
    public function denormalizeDataProvider()
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
            ]
        ];
    }
}
