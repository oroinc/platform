<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ImportExport\Serializer;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ImportExport\Serializer\EntityFieldFallbackValueNormalizer;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityFieldFallbackValueNormalizerTest extends TestCase
{
    use EntityTrait;

    private EntityFallbackResolver&MockObject $resolver;
    private LocaleSettings&MockObject $localeSettings;
    private EntityFieldFallbackValueNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->resolver = $this->createMock(EntityFallbackResolver::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->normalizer = new EntityFieldFallbackValueNormalizer($this->resolver, $this->localeSettings);
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(object $object, ?array $expected = null): void
    {
        $this->assertSame($expected, $this->normalizer->normalize($object));
    }

    public function normalizeDataProvider(): array
    {
        return [
            'scalar' => [
                $this->getEntity(
                    EntityFieldFallbackValue::class,
                    ['fallback' => null, 'scalar_value' => 'val']
                ),
                ['value' => 'val']
            ],
            'array' => [
                $this->getEntity(
                    EntityFieldFallbackValue::class,
                    ['fallback' => null, 'array_value' => ['val1']]
                ),
                ['value' => ['val1']]
            ],
            'unsupported' => [
                new \stdClass(),
                null
            ],
            'fallback' => [
                $this->getEntity(
                    EntityFieldFallbackValue::class,
                    ['fallback' => 'category', 'scalar_value' => 'val']
                ),
                ['value' => 'category']
            ]
        ];
    }

    /**
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize(string $value, bool $isFallbackConfigure, object $expected): void
    {
        $this->localeSettings->expects($this->never())
            ->method('getLocale');

        $context = ['entityName' => \stdClass::class, 'fieldName' => 'some_field_name'];
        $this->resolver->expects($this->once())
            ->method('isFallbackConfigured')
            ->with($value, $context['entityName'], $context['fieldName'])
            ->willReturn($isFallbackConfigure);

        $this->assertEquals($expected, $this->normalizer->denormalize(
            ['value' => $value],
            EntityFieldFallbackValue::class,
            null,
            $context
        ));
    }

    public function denormalizeDataProvider(): array
    {
        return [
            'scalar value' => ['val', false, $this->getEntity(
                EntityFieldFallbackValue::class,
                ['fallback' => null, 'scalarValue' => 'val']
            )],
            'fallback' => ['category', true, $this->getEntity(
                EntityFieldFallbackValue::class,
                ['fallback' => 'category', 'scalarValue' => null]
            )],
            'no fallback' => ['category', false, $this->getEntity(
                EntityFieldFallbackValue::class,
                ['fallback' => null, 'scalarValue' => 'category']
            )],
        ];
    }

    /**
     * @dataProvider denormalizeDecimalValueDataProvider
     */
    public function testDenormalizeDecimalValue(mixed $value, string $type, string $locale, mixed $expected): void
    {
        $this->resolver->expects($this->once())
            ->method('getType')
            ->with(\stdClass::class, 'some_field_name')
            ->willReturn($type);

        $this->localeSettings->expects($this->once())
            ->method('getLocale')
            ->willReturn($locale);

        $fallbackValue = $this->normalizer->denormalize(
            ['value' => $value],
            EntityFieldFallbackValue::class,
            null,
            ['entityName' => \stdClass::class, 'fieldName' => 'some_field_name']
        );

        $this->assertSame($expected, $fallbackValue->getScalarValue());
    }

    public function denormalizeDecimalValueDataProvider(): array
    {
        return [
            ['123', EntityFallbackResolver::TYPE_DECIMAL, 'en', 123],
            ['-123', EntityFallbackResolver::TYPE_DECIMAL, 'en', -123],
            ['123.45', EntityFallbackResolver::TYPE_DECIMAL, 'en', 123.45],
            ['123,45', EntityFallbackResolver::TYPE_DECIMAL, 'en', '123,45'],
            ['-123.45', EntityFallbackResolver::TYPE_DECIMAL, 'en', -123.45],
            ['-123,45', EntityFallbackResolver::TYPE_DECIMAL, 'en', '-123,45'],
            ['123.456,78', EntityFallbackResolver::TYPE_DECIMAL, 'en', '123.456,78'],
            ['123,456', EntityFallbackResolver::TYPE_INTEGER, 'en', 123456],
            ['123,456.78', EntityFallbackResolver::TYPE_DECIMAL, 'en', 123456.78 ],
            ['123', EntityFallbackResolver::TYPE_DECIMAL, 'nl', 123],
            ['-123', EntityFallbackResolver::TYPE_DECIMAL, 'nl', -123],
            ['123.45', EntityFallbackResolver::TYPE_DECIMAL, 'nl', '123.45'],
            ['123,45', EntityFallbackResolver::TYPE_DECIMAL, 'nl', 123.45],
            ['-123.45', EntityFallbackResolver::TYPE_DECIMAL, 'nl', '-123.45'],
            ['-123,45', EntityFallbackResolver::TYPE_DECIMAL, 'nl', -123.45],
            ['123.456,78', EntityFallbackResolver::TYPE_DECIMAL, 'nl', 123456.78],
            ['123.456', EntityFallbackResolver::TYPE_INTEGER, 'nl', 123456],
            ['123,456.78', EntityFallbackResolver::TYPE_DECIMAL, 'nl', '123,456.78'],
            [
                '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890' .
                '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890' .
                '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890' .
                '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
                EntityFallbackResolver::TYPE_DECIMAL,
                'en',
                '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890' .
                '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890' .
                '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890' .
                '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
            ],
            [
                '-123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789' .
                '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890' .
                '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890' .
                '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
                EntityFallbackResolver::TYPE_DECIMAL,
                'en',
                '-123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789' .
                '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890' .
                '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890' .
                '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
            ],
        ];
    }

    public function testSupportsDenormalization(): void
    {
        $this->assertTrue($this->normalizer->supportsDenormalization([], EntityFieldFallbackValue::class));
    }

    public function testDoesNotSupportsDenormalization(): void
    {
        $this->assertFalse($this->normalizer->supportsDenormalization([], \stdClass::class));
    }

    public function testSupportsNormalization(): void
    {
        $this->assertTrue($this->normalizer->supportsNormalization(new EntityFieldFallbackValue()));
    }

    public function testDoesNotSupportsNormalization(): void
    {
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }
}
