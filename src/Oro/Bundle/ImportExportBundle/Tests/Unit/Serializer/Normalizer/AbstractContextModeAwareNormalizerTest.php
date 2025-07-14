<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\AbstractContextModeAwareNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\RuntimeException;

class AbstractContextModeAwareNormalizerTest extends TestCase
{
    private AbstractContextModeAwareNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->normalizer = $this->getMockBuilder(AbstractContextModeAwareNormalizer::class)
            ->setConstructorArgs([['import', 'export'], 'import'])
            ->getMockForAbstractClass();
    }

    public function testNormalizeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Normalization with mode "import" is not supported');

        $this->normalizer->normalize(new \stdClass());
    }

    public function testNormalizeUnsupportedMode(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Mode "unknown" is not supported');

        $this->normalizer->normalize(new \stdClass(), null, ['mode' => 'unknown']);
    }

    public function testConstructorException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Mode "unknown" is not supported, available modes are "import", export"');

        $this->normalizer = $this->getMockBuilder(AbstractContextModeAwareNormalizer::class)
            ->setConstructorArgs([['import', 'export'], 'unknown'])
            ->getMockForAbstractClass();
    }

    public function testDenormalizeUnsupportedMode(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Denormalization with mode "import" is not supported');

        $this->normalizer->denormalize('test', \stdClass::class);
    }
}
