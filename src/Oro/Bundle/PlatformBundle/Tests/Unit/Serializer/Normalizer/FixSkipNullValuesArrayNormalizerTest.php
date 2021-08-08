<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\PlatformBundle\Serializer\Normalizer\FixSkipNullValuesArrayNormalizer;

class FixSkipNullValuesArrayNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FixSkipNullValuesArrayNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new FixSkipNullValuesArrayNormalizer();
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(array $data, array $context, array $result = null): void
    {
        if (null === $result) {
            $result = $data;
        }
        self::assertSame($result, $this->normalizer->normalize($data, 'some_format', $context));
    }

    public function normalizeDataProvider(): array
    {
        return [
            [['key1' => 0, 'key2' => null, 'key3' => 'val'], []],
            [['key1' => 0, 'key2' => null, 'key3' => 'val'], ['skip_null_values' => false]],
            [
                ['key1' => 0, 'key2' => null, 'key3' => 'val'],
                ['skip_null_values' => true],
                ['key1' => 0, 'key3' => 'val']
            ],
            [
                [['key1' => 0, 'key2' => null, 'key3' => 'val']],
                ['skip_null_values' => true],
                [['key1' => 0, 'key3' => 'val']]
            ],
            [
                [['key1' => 0, 'key2' => null, 'key3' => [['key1' => 0, 'key2' => null, 'key3' => 'val']]]],
                ['skip_null_values' => true],
                [['key1' => 0, 'key3' => [['key1' => 0, 'key3' => 'val']]]]
            ],
        ];
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization(mixed $data, bool $result): void
    {
        self::assertSame($result, $this->normalizer->supportsNormalization($data, 'some_format'));
    }

    public function supportsNormalizationDataProvider(): array
    {
        return [
            [null, false],
            ['', false],
            [new \stdClass(), false],
            [[], false],
            [[1, 2, 3], true],
            [['key' => 'value'], true]
        ];
    }
}
