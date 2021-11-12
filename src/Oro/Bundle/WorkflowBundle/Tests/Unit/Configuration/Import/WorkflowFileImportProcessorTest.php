<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Import\WorkflowFileImportProcessor;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Symfony\Component\Config\FileLocatorInterface;

class WorkflowFileImportProcessorTest extends \PHPUnit\Framework\TestCase
{
    private const FILE_PATH = './filePath';

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

    private function applyImportOptions(
        WorkflowFileImportProcessor $processor,
        string $resource,
        string $target,
        array $replacements = []
    ): void {
        $processor->setResource($resource);
        $processor->setTarget($target);
        $processor->setReplacements($replacements);
    }

    public function testProcess(): void
    {
        $contentImported = ['workflows' => [
            'import' => [
                'distinct' => 'value',
                'node' => ['to_replace' => 'unneeded data', 'not replaced' => 'yay'],
                'numeric_array' => ['element2'],
            ]
        ]];

        $contentToProcess = ['workflows' => [
            'target' => ['distinct' => 'merge', 'some' => 'content', 'numeric_array' => ['element1']]
        ]];

        $result = ['workflows' => [
            'target' => [
                'distinct' => 'value', // replaced from the imported config
                'node' => ['not replaced' => 'yay'],
                'some' => 'content',
                'numeric_array' => ['element1', 'element2'], // New element is appended after existing.
            ]
        ]];

        $processor = new WorkflowFileImportProcessor($this->reader, 'test.yml', $this->fileLocator);
        $this->applyImportOptions($processor, 'import', 'target', ['node.to_replace']);

        $contentSource = new \SplFileInfo('/path/to/source');

        $this->reader->expects($this->once())
            ->method('read')
            ->with($this->callback(function (\SplFileInfo $importFile) {
                $this->assertSame($importFile->getPathname(), '/path/to/test.yml');

                return true;
            }))
            ->willReturn($contentImported);

        $this->parentProcessor->expects($this->never())
            ->method('process');

        $this->fileLocator->expects($this->never())
            ->method('locate');

        $processed = $processor->process($contentToProcess, $contentSource);

        $this->assertEquals($result, $processed);
    }

    public function testProcessWithFileLocator(): void
    {
        $contentImported = ['workflows' => [
            'import' => [
                'distinct' => 'value',
                'node' => ['to_replace' => 'unneeded data', 'not replaced' => 'yay']
            ]
        ]];

        $contentToProcess = ['workflows' => [
            'target' => ['distinct' => 'merge', 'some' => 'content']
        ]];

        $result = ['workflows' => [
            'target' => [
                'distinct' => 'value', // replaced from the imported config
                'node' => ['not replaced' => 'yay'],
                'some' => 'content'
            ]
        ]];

        $relativeFileResource = '@AcmeDemoBundle:workflow/test.yml';
        $processor = new WorkflowFileImportProcessor($this->reader, $relativeFileResource, $this->fileLocator);
        $this->applyImportOptions($processor, 'import', 'target', ['node.to_replace']);

        $contentSource = new \SplFileInfo('/path/to/source');

        $this->reader->expects($this->once())
            ->method('read')
            ->with($this->callback(function (\SplFileInfo $importFile) {
                $this->assertSame(
                    $importFile->getPathname(),
                    '/full/path/to/bundle/Resources/config/oro/workflow/test.yml'
                );

                return true;
            }))
            ->willReturn($contentImported);

        $this->parentProcessor->expects($this->never())
            ->method('process');

        $this->fileLocator->expects($this->once())
            ->method('locate')
            ->with($relativeFileResource)
            ->willReturn('/full/path/to/bundle/Resources/config/oro/workflow/test.yml');

        $processed = $processor->process($contentToProcess, $contentSource);

        $this->assertEquals($result, $processed);
    }

    public function testProcessWithParentCall(): void
    {
        $contentToProcess = ['workflows' => [
            'target' => ['distinct' => 'merge', 'some' => 'content']
        ]];

        $contentFromFile = [
            'import' => ['suppose another import that would be processed by parent'],
            'workflows' => [
                'import' => [
                    'distinct' => 'value',
                    'node' => ['to_replace' => 'unneeded data', 'not replaced' => 'yay']
                ]
            ]
        ];

        $contentModifiedByParentProcess = ['workflows' => [
            'import' => [
                'distinct' => 'value',
                'node' => ['to_replace' => 'unneeded data', 'not replaced' => 'yay', 'added' => 'by parent']
            ]
        ]];

        $result = ['workflows' => [
            'target' => [
                'distinct' => 'value', // replaced from the imported config
                'node' => ['not replaced' => 'yay', 'added' => 'by parent'],
                'some' => 'content'
            ]
        ]];

        $processor = new WorkflowFileImportProcessor($this->reader, 'test.yml', $this->fileLocator);
        $this->applyImportOptions($processor, 'import', 'target', ['node.to_replace']);

        $contentSource = new \SplFileInfo('/path/to/source');

        $this->reader->expects($this->once())
            ->method('read')
            ->with($this->callback(function (\SplFileInfo $importFile) {
                $this->assertSame($importFile->getPathname(), '/path/to/test.yml');

                return true;
            }))
            ->willReturn($contentFromFile);

        $this->parentProcessor->expects($this->once())
            ->method('process')
            ->with($contentFromFile, $contentSource)
            ->willReturn($contentModifiedByParentProcess);

        $this->fileLocator->expects($this->never())
            ->method('locate');

        $processor->setParent($this->parentProcessor);
        $processed = $processor->process($contentToProcess, $contentSource);

        $this->assertEquals($result, $processed);
    }

    public function testProcessWithParentCallWithFileLocator(): void
    {
        $contentToProcess = ['workflows' => [
            'target' => ['distinct' => 'merge', 'some' => 'content']
        ]];

        $contentFromFile = [
            'import' => ['suppose another import that would be processed by parent'],
            'workflows' => [
                'import' => [
                    'distinct' => 'value',
                    'node' => ['to_replace' => 'unneeded data', 'not replaced' => 'yay']
                ]
            ]
        ];

        $contentModifiedByParentProcess = ['workflows' => [
            'import' => [
                'distinct' => 'value',
                'node' => ['to_replace' => 'unneeded data', 'not replaced' => 'yay', 'added' => 'by parent']
            ]
        ]];

        $result = ['workflows' => [
            'target' => [
                'distinct' => 'value', // replaced from the imported config
                'node' => ['not replaced' => 'yay', 'added' => 'by parent'],
                'some' => 'content'
            ]
        ]];

        $relativeFileResource = '@AcmeDemoBundle:workflow/test.yml';
        $processor = new WorkflowFileImportProcessor($this->reader, $relativeFileResource, $this->fileLocator);
        $this->applyImportOptions($processor, 'import', 'target', ['node.to_replace']);

        $contentSource = new \SplFileInfo('/path/to/source');

        $this->reader->expects($this->once())
            ->method('read')
            ->with($this->callback(function (\SplFileInfo $importFile) {
                $this->assertSame(
                    $importFile->getPathname(),
                    '/full/path/to/bundle/Resources/config/oro/workflow/test.yml'
                );

                return true;
            }))
            ->willReturn($contentFromFile);

        $this->parentProcessor->expects($this->once())
            ->method('process')
            ->with($contentFromFile, $contentSource)
            ->willReturn($contentModifiedByParentProcess);

        $this->fileLocator->expects($this->once())
            ->method('locate')
            ->with($relativeFileResource)
            ->willReturn('/full/path/to/bundle/Resources/config/oro/workflow/test.yml');

        $processor->setParent($this->parentProcessor);
        $processed = $processor->process($contentToProcess, $contentSource);

        $this->assertEquals($result, $processed);
    }
}
