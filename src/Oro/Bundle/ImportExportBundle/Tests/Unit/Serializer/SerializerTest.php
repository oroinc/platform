<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Serializer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;

class SerializerTest extends \PHPUnit\Framework\TestCase
{
    private Serializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new Serializer();
    }

    public function testConstruct(): void
    {
        self::assertInstanceOf(SymfonySerializer::class, $this->serializer);
    }

    public function testGetNormalizer(): void
    {
        $supportedNormalizer = $this->createMock(ContextAwareNormalizerInterface::class);
        $supportedNormalizer->expects(self::once())
            ->method('supportsNormalization')
            ->willReturn(true);

        $nonSupportedNormalizer = $this->createMock(ContextAwareNormalizerInterface::class);
        $nonSupportedNormalizer->expects(self::once())
            ->method('supportsNormalization')
            ->willReturn(false);

        $denormalizer = $this->createMock(ContextAwareDenormalizerInterface::class);
        $denormalizer->expects(self::never())
            ->method('supportsDenormalization')
            ->willReturn(true);

        $this->serializer = new Serializer([$denormalizer, $nonSupportedNormalizer, $supportedNormalizer]);

        $this->serializer->supportsNormalization(new \stdClass());
    }

    public function testGetDenormalizer(): void
    {
        $normalizer = $this->createMock(ContextAwareNormalizerInterface::class);
        $normalizer->expects(self::never())
            ->method('supportsNormalization')
            ->willReturn(true);

        $supportedDenormalizer = $this->createMock(ContextAwareDenormalizerInterface::class);
        $supportedDenormalizer->expects(self::once())
            ->method('supportsDenormalization')
            ->willReturn(true);

        $nonSupportedDenormalizer = $this->createMock(ContextAwareDenormalizerInterface::class);
        $nonSupportedDenormalizer->expects(self::once())
            ->method('supportsDenormalization')
            ->willReturn(false);

        $this->serializer = new Serializer([$normalizer, $nonSupportedDenormalizer, $supportedDenormalizer]);

        $this->serializer->supportsDenormalization(new \stdClass(), 'test');
    }

    public function testGetNormalizerFailed(): void
    {
        $this->serializer = new Serializer();

        $this->serializer->supportsNormalization(new \stdClass(), 'test');
    }

    public function testGetDenormalizerFailed(): void
    {
        $this->serializer = new Serializer();

        $this->serializer->supportsDenormalization(new \stdClass(), 'test');
    }

    /**
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize(string $proc, string $procForCompare, int $iterations): void
    {
        $normalizer = $this->createMock(ContextAwareNormalizerInterface::class);

        $supportedDenormalizer = $this->createMock(ContextAwareDenormalizerInterface::class);
        $supportedDenormalizer->expects(self::exactly($iterations))
            ->method('supportsDenormalization')
            ->willReturn(true);

        $nonSupportedDenormalizer = $this->createMock(ContextAwareDenormalizerInterface::class);
        $nonSupportedDenormalizer->expects(self::exactly($iterations))
            ->method('supportsDenormalization')
            ->willReturn(false);

        $this->serializer = new Serializer([$normalizer, $nonSupportedDenormalizer, $supportedDenormalizer]);

        $this->serializer->supportsDenormalization(new \stdClass(), 'test');
        $this->serializer->denormalize(new \stdClass(), 'test', null, ['processorAlias' => $proc]);
        $this->serializer->denormalize(
            new \stdClass(),
            'test',
            null,
            ['processorAlias' => $procForCompare]
        );
    }

    public function denormalizeDataProvider(): array
    {
        return [
            'with cache' => ['proc', 'proc', 2],
            'without cache' => ['proc', 'proc1', 3],
        ];
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(string $proc, string $procForCompare, int $iterations): void
    {
        $supportedNormalizer = $this->createMock(ContextAwareNormalizerInterface::class);
        $supportedNormalizer->expects(self::exactly($iterations))
            ->method('supportsNormalization')
            ->willReturn(true);

        $nonSupportedNormalizer = $this->createMock(ContextAwareNormalizerInterface::class);
        $nonSupportedNormalizer->expects(self::exactly($iterations))
            ->method('supportsNormalization')
            ->willReturn(false);

        $denormalizer = $this->createMock(ContextAwareDenormalizerInterface::class);

        $this->serializer = new Serializer([$denormalizer, $nonSupportedNormalizer, $supportedNormalizer]);

        $this->serializer->supportsNormalization(new \stdClass());
        $this->serializer->normalize(new \stdClass(), null, ['processorAlias' => $proc]);
        $this->serializer->normalize(
            new \stdClass(),
            null,
            ['processorAlias' => $procForCompare]
        );
    }

    public function normalizeDataProvider(): array
    {
        return [
            'with cache' => ['proc', 'proc', 4],
            'without cache' => ['proc', 'proc1', 5],
        ];
    }
}
