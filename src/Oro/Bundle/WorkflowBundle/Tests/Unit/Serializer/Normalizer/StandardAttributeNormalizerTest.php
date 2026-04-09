<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\StandardAttributeNormalizer;
use Oro\Component\PhpUtils\Exception\UnsafeUnserializationException;
use Oro\Component\PhpUtils\PhpUnserializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StandardAttributeNormalizerTest extends TestCase
{
    private Workflow&MockObject $workflow;
    private Attribute&MockObject $attribute;
    private LoggerInterface&MockObject $logger;
    private PhpUnserializerInterface&MockObject $unserializer;

    private StandardAttributeNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflow = $this->createMock(Workflow::class);
        $this->attribute = $this->createMock(Attribute::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->unserializer = $this->createMock(PhpUnserializerInterface::class);

        $this->normalizer = new StandardAttributeNormalizer($this->logger, $this->unserializer);
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
        ];
    }

    public function testDenormalizeArrayWhenValueIsNotString()
    {
        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn('array');

        $this->unserializer->expects($this->never())->method('unserialize');

        $this->assertEquals([], $this->normalizer->denormalize($this->workflow, $this->attribute, false));
    }

    public function testDenormalizeArrayWhenUnserializedValueIsNotArray(): void
    {
        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn('array');

        $this->unserializer->expects($this->once())
            ->method('unserialize')
            ->with(serialize('somestring'), ['allowed_classes' => false])
            ->willReturn('somestring');

        $this->assertEquals(
            [],
            $this->normalizer->denormalize($this->workflow, $this->attribute, $this->serializeBase64('somestring'))
        );
    }

    public function testDenormalizeArray(): void
    {
        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn('array');

        $this->unserializer->expects($this->once())
            ->method('unserialize')
            ->with(serialize([1, 2, 3]), ['allowed_classes' => false])
            ->willReturn([1, 2, 3]);

        $this->assertEquals(
            [1, 2, 3],
            $this->normalizer->denormalize($this->workflow, $this->attribute, $this->serializeBase64([1, 2, 3]))
        );
    }

    public function testDenormalizeObjectWhenUnserializedValueIsNotObject(): void
    {
        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn('object');
        $this->attribute->expects($this->exactly(2))
            ->method('getOption')
            ->with('class')
            ->willReturn(\stdClass::class);

        $this->unserializer->expects($this->once())
            ->method('unserialize')
            ->with(serialize('01.1'), [PhpUnserializerInterface::WHITELIST_CLASSES_KEY => [\stdClass::class]])
            ->willReturn('01.1');

        $this->assertNull(
            $this->normalizer->denormalize($this->workflow, $this->attribute, $this->serializeBase64('01.1'))
        );
    }

    public function testDenormalizeObjectWhenUnserializedValueIsNotInstanceOfClass(): void
    {
        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn('object');
        $this->attribute->expects($this->exactly(2))
            ->method('getOption')
            ->with('class')
            ->willReturn(\stdClass::class);

        $dateTime = new \DateTime('2011-11-11 11:11:11', new \DateTimeZone('UTC'));

        $this->unserializer->expects($this->once())
            ->method('unserialize')
            ->with(serialize($dateTime), [PhpUnserializerInterface::WHITELIST_CLASSES_KEY => [\stdClass::class]])
            ->willReturn($dateTime);

        $this->assertNull(
            $this->normalizer->denormalize($this->workflow, $this->attribute, $this->serializeBase64($dateTime))
        );
    }

    public function testDenormalizeObject(): void
    {
        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn('object');
        $this->attribute->expects($this->exactly(2))
            ->method('getOption')
            ->with('class')
            ->willReturn(\DateTime::class);

        $dateTime = new \DateTime('2011-11-11 11:11:11', new \DateTimeZone('UTC'));

        $this->unserializer->expects($this->once())
            ->method('unserialize')
            ->with(serialize($dateTime), [PhpUnserializerInterface::WHITELIST_CLASSES_KEY => [\DateTime::class]])
            ->willReturn($dateTime);

        $this->assertEquals(
            $dateTime,
            $this->normalizer->denormalize($this->workflow, $this->attribute, $this->serializeBase64($dateTime))
        );
    }

    public function testDenormalizeArrayLogsErrorWhenBase64DecodeFails(): void
    {
        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn('array');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to base64 decode workflow attribute value', $this->anything());

        $result = $this->normalizer->denormalize($this->workflow, $this->attribute, '!invalid-base64!');

        $this->assertEquals([], $result);
    }

    public function testDenormalizeObjectLogsErrorWhenBase64DecodeFails(): void
    {
        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn('object');
        $this->attribute->expects($this->once())
            ->method('getOption')->with('class')
            ->willReturn(\stdClass::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to base64 decode workflow attribute value', $this->anything());

        $result = $this->normalizer->denormalize($this->workflow, $this->attribute, '!invalid-base64!');

        $this->assertNull($result);
    }

    public function testDenormalizeObjectLogsCriticalOnUnsafeUnserialization(): void
    {
        $this->attribute->expects($this->once())
            ->method('getType')
            ->willReturn('object');
        $this->attribute->expects($this->any())
            ->method('getOption')->with('class')
            ->willReturn(\stdClass::class);

        $exception = UnsafeUnserializationException::create(['ArrayObject']);
        $arrayObject = new \ArrayObject();
        $this->unserializer->expects($this->once())
            ->method('unserialize')
            ->with(serialize($arrayObject), [PhpUnserializerInterface::WHITELIST_CLASSES_KEY => [\stdClass::class]])
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with(
                'Failed to unserialize workflow attribute value',
                $this->callback(
                    fn (array $context) => $context['exception'] instanceof UnsafeUnserializationException
                        && $context['attribute'] === $this->attribute
                )
            );

        // The attribute class ('stdClass') is whitelisted, but the serialized value contains
        // \ArrayObject which is neither in the whitelist nor in an allowed org namespace,
        // so PhpUnserializer must still throw UnsafeUnserializationException.
        $result = $this->normalizer->denormalize(
            $this->workflow,
            $this->attribute,
            $this->serializeBase64($arrayObject)
        );

        $this->assertNull($result);
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
