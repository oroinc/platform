<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Import\ResourceFileImportProcessor;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Symfony\Component\Config\FileLocatorInterface;

class ResourceFileImportProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigFileReaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $reader;

    /** @var FileLocatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fileLocator;

    /** @var ConfigImportProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $parentProcessor;

    protected function setUp(): void
    {
        $this->reader = $this->createMock(ConfigFileReaderInterface::class);
        $this->fileLocator = $this->createMock(FileLocatorInterface::class);
        $this->parentProcessor = $this->createMock(ConfigImportProcessorInterface::class);
    }

    public function testProcessWithoutParent(): void
    {
        $content = ['a' => ['b' => 'c']];
        $contentSource = new \SplFileInfo('/path/to/source');
        $relativeFileResource = 'test.yml';
        $processor = new ResourceFileImportProcessor($this->reader, $relativeFileResource, $this->fileLocator);

        $this->reader->expects($this->once())
            ->method('read')
            ->with($this->callback(function (\SplFileInfo $importFile) {
                $this->assertSame($importFile->getPathname(), '/path/to/test.yml');

                return true;
            }))
            ->willReturn(['a' => ['b' => 'd']]);

        $this->parentProcessor->expects($this->never())
            ->method('process');

        $this->fileLocator->expects($this->never())
            ->method('locate');

        $result = $processor->process($content, $contentSource);

        $this->assertSame(['a' => ['b' => 'd']], $result);
    }

    public function testProcessWithoutParentWithFileLocator(): void
    {
        $content = ['a' => ['b' => 'c']];
        $contentSource = new \SplFileInfo('/path/to/source');
        $relativeFileResource = '@AcmeDemoBundle:workflow/test.yml';
        $processor = new ResourceFileImportProcessor($this->reader, $relativeFileResource, $this->fileLocator);

        $this->reader->expects($this->once())
            ->method('read')
            ->with($this->callback(function (\SplFileInfo $importFile) {
                $this->assertSame(
                    $importFile->getPathname(),
                    '/full/path/to/bundle/Resources/config/oro/workflow/test.yml'
                );

                return true;
            }))
            ->willReturn(['a' => ['b' => 'd']]);

        $this->parentProcessor->expects($this->never())
            ->method('process');

        $this->fileLocator->expects($this->once())
            ->method('locate')
            ->with($relativeFileResource)
            ->willReturn('/full/path/to/bundle/Resources/config/oro/workflow/test.yml');

        $result = $processor->process($content, $contentSource);

        $this->assertSame(['a' => ['b' => 'd']], $result);
    }

    public function testProcessWithParent(): void
    {
        $content = ['a' => ['b' => 'c']];
        $contentSource = new \SplFileInfo('/path/to/source');
        $relativeFileResource = 'test.yml';
        $processor = new ResourceFileImportProcessor($this->reader, $relativeFileResource, $this->fileLocator);

        $this->reader->expects($this->once())
            ->method('read')
            ->with($this->callback(function (\SplFileInfo $importFile) {
                $this->assertSame($importFile->getPathname(), '/path/to/test.yml');

                return true;
            }))
            ->willReturn(['a' => ['b' => 'd']]);

        $this->parentProcessor->expects($this->once())
            ->method('process')
            ->with(['a' => ['b' => 'd']], $contentSource)
            ->willReturn(['a' => ['b' => 'e']]);

        $this->fileLocator->expects($this->never())
            ->method('locate');

        $processor->setParent($this->parentProcessor);
        $result = $processor->process($content, $contentSource);

        $this->assertSame(['a' => ['b' => 'e']], $result);
    }

    public function testProcessWithParentWithFileLocator(): void
    {
        $content = ['a' => ['b' => 'c']];
        $contentSource = new \SplFileInfo('/path/to/source');
        $relativeFileResource = '@AcmeDemoBundle:workflow/test.yml';
        $processor = new ResourceFileImportProcessor($this->reader, $relativeFileResource, $this->fileLocator);

        $this->reader->expects($this->once())
            ->method('read')
            ->with($this->callback(function (\SplFileInfo $importFile) {
                $this->assertSame(
                    $importFile->getPathname(),
                    '/full/path/to/bundle/Resources/config/oro/workflow/test.yml'
                );

                return true;
            }))
            ->willReturn(['a' => ['b' => 'd']]);

        $this->parentProcessor->expects($this->once())
            ->method('process')
            ->with(['a' => ['b' => 'd']], $contentSource)
            ->willReturn(['a' => ['b' => 'e']]);

        $this->fileLocator->expects($this->once())
            ->method('locate')
            ->with($relativeFileResource)
            ->willReturn('/full/path/to/bundle/Resources/config/oro/workflow/test.yml');

        $processor->setParent($this->parentProcessor);
        $result = $processor->process($content, $contentSource);

        $this->assertSame(['a' => ['b' => 'e']], $result);
    }
}
