<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit;

use Oro\Bundle\AttachmentBundle\ProcessorHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;

class ProcessorHelperTest extends TestCase
{
    use CheckProcessorsTrait;

    private ProcessorHelper $processorHelper;

    protected function setUp(): void
    {
        $this->checkProcessors();

        $jpegoptimBinaryPath = ProcessorHelper::findBinary(ProcessorHelper::JPEGOPTIM);
        $pngquantBinaryPath = ProcessorHelper::findBinary(ProcessorHelper::PNGQUANT);

        $this->processorHelper = new ProcessorHelper(
            $jpegoptimBinaryPath ?? '',
            $pngquantBinaryPath ?? '',
            new NullAdapter()
        );
    }

    public function testGetPNGQuantLibrary()
    {
        self::assertNotEmpty($this->processorHelper->getPNGQuantLibrary());
    }

    public function testGetJPEGOptimLibrary()
    {
        self::assertNotEmpty($this->processorHelper->getJPEGOptimLibrary());
    }

    public function testLibrariesExists()
    {
        self::assertTrue($this->processorHelper->librariesExists());
    }
}
