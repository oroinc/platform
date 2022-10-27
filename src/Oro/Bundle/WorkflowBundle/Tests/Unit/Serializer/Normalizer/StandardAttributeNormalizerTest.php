<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\StandardAttributeNormalizer;

class StandardAttributeNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject */
    private $workflow;

    /** @var Attribute|\PHPUnit\Framework\MockObject\MockObject */
    private $attribute;

    /** @var StandardAttributeNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->workflow = $this->createMock(Workflow::class);
        $this->attribute = $this->createMock(Attribute::class);

        $this->normalizer = new StandardAttributeNormalizer();
    }

    /**
     * @dataProvider normalizeScalarsAndArrayDataProvider
     */
    public function testNormalizeScalarsAndArray(string $type, mixed $value, mixed $expected)
    {
        $this->workflow->expects($this->never())
            ->method($this->anything());

        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn($type);

        $this->assertEquals($expected, $this->normalizer->normalize($this->workflow, $this->attribute, $value));
    }

    public function normalizeScalarsAndArrayDataProvider(): array
    {
        return [
            'string' => [
                'type' => 'string',
                'value' => '000',
                'expected' => '000',
            ],
            'string_object' => [
                'type' => 'string',
                'value' => new \stdClass(),
                'expected' => null,
            ],
            'int' => [
                'type' => 'int',
                'value' => '01.1',
                'expected' => 1,
            ],
            'integer' => [
                'type' => 'integer',
                'value' => '-12345.67',
                'expected' => -12345,
            ],
            'bool' => [
                'type' => 'bool',
                'value' => '',
                'expected' => false,
            ],
            'boolean' => [
                'type' => 'boolean',
                'value' => 'false',
                'expected' => true,
            ],
            'float' => [
                'type' => 'float',
                'value' => '-12345.67',
                'expected' => -12345.67,
            ],
            'not_array' => [
                'type' => 'array',
                'value' => '-12345.67',
                'expected' => $this->serializeBase64([]),
            ],
            'array' => [
                'type' => 'array',
                'value' => [1, 2, 3],
                'expected' => $this->serializeBase64([1, 2, 3]),
            ],
        ];
    }

    /**
     * @dataProvider normalizeObjectDataProvider
     */
    public function testNormalizeObject(mixed $value, string $class, ?string $expected)
    {
        $type = 'object';

        $this->workflow->expects($this->never())
            ->method($this->anything());

        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn($type);
        $this->attribute->expects($this->once())
            ->method('getOption')->with('class')
            ->willReturn($class);

        $this->assertEquals($expected, $this->normalizer->normalize($this->workflow, $this->attribute, $value));
    }

    public function normalizeObjectDataProvider(): array
    {
        return [
            'not_object' => [
                'value' => '01.1',
                'class' => 'stdClass',
                'expected' => null,
            ],
            'not_instance_of_class' => [
                'value' => new \DateTime(),
                'class' => 'stdClass',
                'expected' => null,
            ],
            'object' => [
                'value' => new \stdClass(),
                'class' => 'stdClass',
                'expected' => $this->serializeBase64(new \stdClass()),
            ],
        ];
    }

    /**
     * @dataProvider denormalizeScalarsAndArrayDataProvider
     */
    public function testDenormalizeScalarsAndArray(string $type, mixed $value, mixed $expected)
    {
        $this->workflow->expects($this->never())
            ->method($this->anything());

        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn($type);

        $this->assertEquals($expected, $this->normalizer->denormalize($this->workflow, $this->attribute, $value));
    }

    public function denormalizeScalarsAndArrayDataProvider(): array
    {
        return [
            'string' => [
                'type' => 'string',
                'value' => '000',
                'expected' => '000',
            ],
            'string_object' => [
                'type' => 'string',
                'value' => new \stdClass(),
                'expected' => null,
            ],
            'int' => [
                'type' => 'int',
                'value' => '01.1',
                'expected' => 1,
            ],
            'integer' => [
                'type' => 'integer',
                'value' => '-12345.67',
                'expected' => -12345,
            ],
            'bool' => [
                'type' => 'bool',
                'value' => '',
                'expected' => false,
            ],
            'boolean' => [
                'type' => 'boolean',
                'value' => 'false',
                'expected' => true,
            ],
            'float' => [
                'type' => 'float',
                'value' => '-12345.67',
                'expected' => -12345.67,
            ],
            'not_array' => [
                'type' => 'array',
                'value' => false,
                'expected' => [],
            ],
            'not_array_after_unserialized' => [
                'type' => 'array',
                'value' => $this->serializeBase64('somestring'),
                'expected' => [],
            ],
            'array' => [
                'type' => 'array',
                'value' => $this->serializeBase64([1, 2, 3]),
                'expected' => [1, 2, 3],
            ],
        ];
    }

    /**
     * @dataProvider denormalizeObjectDataProvider
     */
    public function testDenormalizeObject(string $value, string $class, ?object $expected)
    {
        $type = 'object';

        $this->workflow->expects($this->never())
            ->method($this->anything());

        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn($type);
        $this->attribute->expects($this->once())
            ->method('getOption')->with('class')
            ->willReturn($class);

        $this->assertEquals($expected, $this->normalizer->denormalize($this->workflow, $this->attribute, $value));
    }

    public function denormalizeObjectDataProvider(): array
    {
        return [
            'not_object' => [
                'value' => $this->serializeBase64('01.1'),
                'class' => 'stdClass',
                'expected' => null,
            ],
            'not_instance_of_class' => [
                'value' => $this->serializeBase64(new \DateTime()),
                'class' => 'stdClass',
                'expected' => null,
            ],
            'object' => [
                'value' => $this->serializeBase64(new \stdClass()),
                'class' => 'stdClass',
                'expected' => new \stdClass(),
            ],
        ];
    }

    /**
     * @dataProvider supportsNormalizeAndDenormalizeDataProvider
     */
    public function testSupportsNormalizeAndDenormalize(string $direction, string $type, bool $expected)
    {
        $attributeValue = 'bar';

        $this->workflow->expects($this->never())
            ->method($this->anything());
        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn($type);

        $method = 'supports' . ucfirst($direction);
        $this->assertEquals($expected, $this->normalizer->$method($this->workflow, $this->attribute, $attributeValue));
    }

    public function supportsNormalizeAndDenormalizeDataProvider(): array
    {
        return [
            ['normalization', 'int', true],
            ['normalization', 'integer', true],
            ['normalization', 'bool', true],
            ['normalization', 'boolean', true],
            ['normalization', 'float', true],
            ['normalization', 'array', true],
            ['normalization', 'object', true],
            ['normalization', 'entity', false],
            ['denormalization', 'int', true],
            ['denormalization', 'integer', true],
            ['denormalization', 'bool', true],
            ['denormalization', 'boolean', true],
            ['denormalization', 'float', true],
            ['denormalization', 'array', true],
            ['denormalization', 'object', true],
            ['denormalization', 'entity', false],
        ];
    }

    private function serializeBase64($value): string
    {
        return base64_encode(serialize($value));
    }
}
