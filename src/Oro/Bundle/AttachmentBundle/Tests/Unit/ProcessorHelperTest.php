<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit;

use Oro\Bundle\AttachmentBundle\ProcessorHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ProcessorHelperTest extends TestCase
{
    use CheckProcessorsTrait;

    private ArrayAdapter $cache;
    private ProcessorHelper $processorHelper;

    protected function setUp(): void
    {
        $this->checkProcessors();

        $this->cache = new ArrayAdapter;
        $this->processorHelper = new ProcessorHelper('', '', $this->cache);
    }

    public function testGetPNGQuantLibrary()
    {
        $this->assertNotEmpty($this->processorHelper->getPNGQuantLibrary());
    }

    public function testGetJPEGOptimLibrary()
    {
        $this->assertNotEmpty($this->processorHelper->getJPEGOptimLibrary());
    }

    public function testLibrariesExists()
    {
        $this->assertTrue($this->processorHelper->librariesExists());
    }
}
