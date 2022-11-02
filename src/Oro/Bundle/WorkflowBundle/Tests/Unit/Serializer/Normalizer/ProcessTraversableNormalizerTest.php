<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessTraversableNormalizer;
use Symfony\Component\Serializer\Serializer;

class ProcessTraversableNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Serializer|\PHPUnit\Framework\MockObject\MockObject */
    private $serializer;

    /** @var ProcessTraversableNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(Serializer::class);

        $this->normalizer = new ProcessTraversableNormalizer();
        $this->normalizer->setSerializer($this->serializer);
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(iterable $data): void
    {
        $format = 'json';
        $context = [];

        $expected = [];

        $normalizeExpectations = [];
        foreach ($data as $key => $value) {
            $normalizeExpectations[] = [$value, $format, $context];
            $expected[$key] = json_encode($value, JSON_THROW_ON_ERROR);
        }
        $this->serializer->expects(self::exactly(count($normalizeExpectations)))
            ->method('normalize')
            ->withConsecutive(...$normalizeExpectations)
            ->willReturnOnConsecutiveCalls(...array_values($expected));

        self::assertSame($expected, $this->normalizer->normalize($data, $format, $context));
    }

    public function normalizeDataProvider(): array
    {
        return [
            'array' => [
                'data' => ['first' => 1, 'second' => 2],
            ],
            'traversable' => [
                'data' => new ArrayCollection(['first' => 1, 'second' => 2]),
            ],
        ];
    }

    public function testDenormalize(): void
    {
        $data = [
            'first' => json_encode(1, JSON_THROW_ON_ERROR),
            'second' => json_encode(2, JSON_THROW_ON_ERROR),
        ];
        $format = 'json';
        $context = [];

        $expected = [];

        $denormalizeExpectations = [];
        foreach ($data as $key => $value) {
            $denormalizeExpectations[] = [$value, '', $format, $context];
            $expected[$key] = json_decode($value, false, 512, JSON_THROW_ON_ERROR);
        }
        $this->serializer->expects(self::exactly(count($denormalizeExpectations)))
            ->method('denormalize')
            ->withConsecutive(...$denormalizeExpectations)
            ->willReturnOnConsecutiveCalls(...array_values($expected));

        self::assertSame($expected, $this->normalizer->denormalize($data, '', $format, $context));
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization(mixed $data, bool $expected): void
    {
        self::assertEquals($expected, $this->normalizer->supportsNormalization($data));
    }

    public function supportsNormalizationDataProvider(): array
    {
        return [
            'null' => ['', false],
            'scalar' => ['scalar', false],
            'array' => [[], true],
            'traversable' => [new ArrayCollection(), true],
        ];
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization(mixed $data, bool $expected): void
    {
        self::assertEquals($expected, $this->normalizer->supportsDenormalization($data, ''));
    }

    public function supportsDenormalizationDataProvider(): array
    {
        return [
            'null' => ['', false],
            'scalar' => ['scalar', false],
            'array' => [[], true],
        ];
    }
}
