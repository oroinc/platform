<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\AbstractContextModeAwareNormalizer;

class AbstractContextModeAwareNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AbstractContextModeAwareNormalizer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = $this
            ->getMockBuilder('Oro\Bundle\ImportExportBundle\Serializer\Normalizer\AbstractContextModeAwareNormalizer')
            ->setConstructorArgs(array(array('import', 'export'), 'import'))
            ->getMockForAbstractClass();
    }

    public function testNormalizeException()
    {
        $this->expectException(\Symfony\Component\Serializer\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Normalization with mode "import" is not supported');

        $this->normalizer->normalize(new \stdClass());
    }

    public function testNormalizeUnsupportedMode()
    {
        $this->expectException(\Symfony\Component\Serializer\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Mode "unknown" is not supported');

        $this->normalizer->normalize(new \stdClass(), null, array('mode' => 'unknown'));
    }

    public function testConstructorException()
    {
        $this->expectException(\Symfony\Component\Serializer\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Mode "unknown" is not supported, available modes are "import", export"');

        $this->normalizer = $this
            ->getMockBuilder('Oro\Bundle\ImportExportBundle\Serializer\Normalizer\AbstractContextModeAwareNormalizer')
            ->setConstructorArgs(array(array('import', 'export'), 'unknown'))
            ->getMockForAbstractClass();
    }

    public function testDenormalizeUnsupportedMode()
    {
        $this->expectException(\Symfony\Component\Serializer\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Denormalization with mode "import" is not supported');

        $this->normalizer->denormalize('test', '\stdClass');
    }
}
