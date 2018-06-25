<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ImportExport\Serializer;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\ImportExport\Serializer\EntityFieldFallbackValueNormalizer;
use Oro\Component\Testing\Unit\EntityTrait;

class EntityFieldFallbackValueNormalizerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @dataProvider normalizeDataProvider
     * @param object $object
     * @param array $context
     * @param array|null $expected
     */
    public function testNormalize($object, array $context = [], array $expected = null)
    {
        $serializer = new EntityFieldFallbackValueNormalizer();
        $this->assertSame($expected, $serializer->normalize($object, null, $context));
    }

    /**
     * @return array
     */
    public function normalizeDataProvider()
    {
        return [
            'scalar' => [
                $this->getEntity(
                    EntityFieldFallbackValue::class,
                    ['fallback' => 'fallback', 'scalar_value' => 'val']
                ),
                [],
                [
                    'fallback' => 'fallback',
                    'value' => 'val'
                ]
            ],
            'array' => [
                $this->getEntity(
                    EntityFieldFallbackValue::class,
                    ['fallback' => 'fallback', 'array_value' => ['val1']]
                ),
                [],
                [
                    'fallback' => 'fallback',
                    'value' => ['val1']
                ]
            ],
            'unsupported' => [
                new \stdClass(),
                [],
                null
            ]
        ];
    }

    /**
     * @dataProvider denormalizeDataProvider
     * @param array|string $data
     * @param object $expected
     */
    public function testDenormalize($data, $expected)
    {
        $serializer = new EntityFieldFallbackValueNormalizer();
        $this->assertEquals($expected, $serializer->denormalize($data, EntityFieldFallbackValue::class));
    }

    /**
     * @return array
     */
    public function denormalizeDataProvider()
    {
        return [
            'scalar value' => [
                [
                    'fallback' => 'fallback',
                    'value' => 'val'
                ],
                $this->getEntity(
                    EntityFieldFallbackValue::class,
                    ['fallback' => 'fallback', 'scalar_value' => 'val']
                ),
            ],
            'array value' => [
                [
                    'fallback' => 'fallback',
                    'value' => ['val1']
                ],
                $this->getEntity(
                    EntityFieldFallbackValue::class,
                    ['fallback' => 'fallback', 'array_value' => ['val1']]
                ),
            ],
            'single scalar' => [
                'val',
                $this->getEntity(
                    EntityFieldFallbackValue::class,
                    ['scalar_value' => 'val']
                ),
            ]
        ];
    }

    public function testSupportsDenormalization()
    {
        $serializer = new EntityFieldFallbackValueNormalizer();
        $this->assertTrue($serializer->supportsDenormalization([], EntityFieldFallbackValue::class));
    }

    public function testDoesNotSupportsDenormalization()
    {
        $serializer = new EntityFieldFallbackValueNormalizer();
        $this->assertFalse($serializer->supportsDenormalization([], \stdClass::class));
    }

    public function testSupportsNormalization()
    {
        $serializer = new EntityFieldFallbackValueNormalizer();
        $this->assertTrue($serializer->supportsNormalization(new EntityFieldFallbackValue()));
    }

    public function testDoesNotSupportsNormalization()
    {
        $serializer = new EntityFieldFallbackValueNormalizer();
        $this->assertFalse($serializer->supportsNormalization(new \stdClass()));
    }
}
