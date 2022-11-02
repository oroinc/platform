<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\PlatformBundle\Serializer\Normalizer\FixSkipNullValuesArrayNormalizer;
use Symfony\Component\Serializer\Serializer;

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

        $serializer = $this->createMock(Serializer::class);
        $serializer->expects(self::any())
            ->method('normalize')
            ->willReturnCallback(function ($data) {
                if ($data instanceof \DateTime) {
                    return $data->format('c');
                }

                return $data;
            });
        $this->normalizer->setSerializer($serializer);

        self::assertSame($result, $this->normalizer->normalize($data, 'some_format', $context));
    }

    public function normalizeDataProvider(): array
    {
        $dt = new \DateTime('2021-01-01 10:30:00', new \DateTimeZone('Europe/Kiev'));
        $formattedDt = $dt->format('c');

        return [
            [['key1' => 0, 'key2' => null, 'key3' => $dt], []],
            [['key1' => 0, 'key2' => null, 'key3' => $dt], ['skip_null_values' => false]],
            [
                ['key1' => 0, 'key2' => null, 'key3' => $dt],
                ['skip_null_values' => true],
                ['key1' => 0, 'key3' => $formattedDt]
            ],
            [
                [['key1' => 0, 'key2' => null, 'key3' => $dt], $dt],
                ['skip_null_values' => true],
                [['key1' => 0, 'key3' => $formattedDt], $formattedDt]
            ],
            [
                [['key1' => 0, 'key2' => null, 'key3' => [['key1' => 0, 'key2' => null, 'key3' => $dt], $dt]]],
                ['skip_null_values' => true],
                [['key1' => 0, 'key3' => [['key1' => 0, 'key3' => $formattedDt], $formattedDt]]]
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
