<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit;

use Oro\Bundle\AttachmentBundle\ProcessorHelper;
use PHPUnit\Framework\TestCase;

class ProcessorHelperTest extends TestCase
{
    use CheckProcessorsTrait;

    private ProcessorHelper $processorHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkProcessors();

        $jpegoptimBinaryPath = ProcessorHelper::findLibrary(ProcessorHelper::JPEGOPTIM);
        $pngquantBinaryPath = ProcessorHelper::findLibrary(ProcessorHelper::PNGQUANT);

        $this->processorHelper = new ProcessorHelper(
            $jpegoptimBinaryPath ?? '',
            $pngquantBinaryPath ?? ''
        );
    }

    public function testGetPNGQuantLibrary(): void
    {
        $this->assertNotEmpty($this->processorHelper->getPNGQuantLibrary());
    }

    public function testGetJPEGOptimLibrary(): void
    {
        $this->assertNotEmpty($this->processorHelper->getJPEGOptimLibrary());
    }

    public function testLibrariesExists(): void
    {
        $this->assertTrue($this->processorHelper->librariesExists());
    }
}
