<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Import\ResourceFileImportProcessor;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;

class ResourceFileImportProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigFileReaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $reader;

    /** @var ConfigImportProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $parentProcessor;

    /** @var \SplFileInfo|\PHPUnit\Framework\MockObject\MockObject */
    private $contentFile;

    /** @var ResourceFileImportProcessor */
    private $processor;

    protected function setUp()
    {
        $this->reader = $this->createMock(ConfigFileReaderInterface::class);
        $this->contentFile = $this->createMock(\SplFileInfo::class);
        $this->parentProcessor = $this->createMock(ConfigImportProcessorInterface::class);

        $this->processor = new ResourceFileImportProcessor($this->reader, 'relative path', ['OroWorkflowBundle']);
    }

    public function testProcessWithoutParent()
    {
        $content = ['a' => ['b' => 'c']];

        $this->contentFile->expects($this->once())->method('getPath')->willReturn('/');

        $importFileStateAssert = function (\SplFileInfo $fileInfo) {
            $this->assertInstanceOf(\SplFileInfo::class, $fileInfo);
            $this->assertSame(sprintf('/%srelative path', DIRECTORY_SEPARATOR), $fileInfo->getPathname());

            return true;
        };

        $this->reader->expects($this->once())->method('read')
            ->with($this->callback($importFileStateAssert))
            ->willReturn(['a' => ['b' => 'd']]);

        $this->parentProcessor->expects($this->never())->method('process');

        $result = $this->processor->process($content, $this->contentFile);

        $this->assertSame(['a' => ['b' => 'd']], $result);
    }

    public function testProcessWithParent()
    {
        $content = ['a' => ['b' => 'c']];

        $this->contentFile->expects($this->once())->method('getPath')->willReturn('/');

        $importFileStateAssert = function (\SplFileInfo $importFile) {
            $this->assertInstanceOf(\SplFileInfo::class, $importFile);
            $this->assertSame(sprintf('/%srelative path', DIRECTORY_SEPARATOR), $importFile->getPathname());
            return true;
        };

        $this->reader->expects($this->once())->method('read')
            ->with($this->callback($importFileStateAssert))
            ->willReturn(['a' => ['b' => 'd']]);

        $this->parentProcessor->expects($this->once())->method('process')
            ->with(['a' => ['b' => 'd']], $this->callback($importFileStateAssert))
            ->willReturn(['a' => ['b' => 'e']]);

        $this->processor->setParent($this->parentProcessor);
        $result = $this->processor->process($content, $this->contentFile);

        $this->assertSame(['a' => ['b' => 'e']], $result);
    }
}
