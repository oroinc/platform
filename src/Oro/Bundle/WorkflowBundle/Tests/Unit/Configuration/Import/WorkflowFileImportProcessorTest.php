<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Import\WorkflowFileImportProcessor;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;

class WorkflowFileImportProcessorTest extends \PHPUnit\Framework\TestCase
{
    const FILE_PATH = './filePath';

    /** @var ConfigFileReaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $reader;

    /** @var ConfigImportProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $parentProcessor;

    /** @var string */
    private $filePath;

    /** @var WorkflowFileImportProcessor */
    private $processor;

    protected function setUp()
    {
        $this->parentProcessor = $this->createMock(ConfigImportProcessorInterface::class);
        $this->reader = $this->createMock(ConfigFileReaderInterface::class);

        $this->processor = new WorkflowFileImportProcessor($this->reader, self::FILE_PATH);
    }

    /**
     * @param string $resource
     * @param string $target
     * @param array $replacements
     */
    private function applyImportOptions(string $resource, string $target, array $replacements = [])
    {
        $this->processor->setResource($resource);
        $this->processor->setTarget($target);
        $this->processor->setReplacements($replacements);
    }

    public function testProcess()
    {
        $contentImported = [
            'workflows' => [
                'import' => [
                    'distinct' => 'value',
                    'node' => [
                        'to_replace' => 'unneeded data',
                        'not replaced' => 'yay'
                    ]
                ]
            ]
        ];

        $contentToProcess = [
            'workflows' => [
                'target' => ['distinct' => 'merge', 'some' => 'content']
            ]
        ];

        $result = [
            'workflows' => [
                'target' => [
                    'distinct' => 'merge', //not default recursive merge behavior
                    'node' => [
                        'not replaced' => 'yay'
                    ],
                    'some' => 'content'
                ]
            ]
        ];

        $this->applyImportOptions('import', 'target', ['node.to_replace']);

        $file = new \SplFileInfo(__FILE__);

        $importFileAssertion = function ($file) {
            /** @var \SplFileInfo $file */
            $this->assertInstanceOf(\SplFileInfo::class, $file);
            $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . self::FILE_PATH, $file->getPathname());

            return true;
        };

        $this->reader->expects($this->once())->method('read')
            ->with($this->callback($importFileAssertion))->willReturn($contentImported);

        $processed = $this->processor->process($contentToProcess, $file);

        $this->assertEquals($result, $processed);
    }

    public function testProcessWithParentCall()
    {
        $contentToProcess = [
            'workflows' => [
                'target' => [
                    'distinct' => 'merge',
                    'some' => 'content'
                ]
            ]
        ];

        $contentFromFile = [
            'import' => ['suppose another import that would be processed by parent'],
            'workflows' => [
                'import' => [
                    'distinct' => 'value',
                    'node' => [
                        'to_replace' => 'unneeded data',
                        'not replaced' => 'yay'
                    ]
                ]
            ]
        ];

        $contentModifiedByParentProcess = [
            'workflows' => [
                'import' => [
                    'distinct' => 'value',
                    'node' => [
                        'to_replace' => 'unneeded data',
                        'not replaced' => 'yay',
                        'added' => 'by parent'
                    ]
                ]
            ]
        ];

        $result = [
            'workflows' => [
                'target' => [
                    'distinct' => 'merge',
                    'node' => [
                        'not replaced' => 'yay',
                        'added' => 'by parent'
                    ],
                    'some' => 'content'
                ]
            ]
        ];

        $this->applyImportOptions('import', 'target', ['node.to_replace']);

        $file = new \SplFileInfo(__FILE__);

        $importFileAssertion = function ($file) {
            /** @var \SplFileInfo $file */
            $this->assertInstanceOf(\SplFileInfo::class, $file);
            $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . self::FILE_PATH, $file->getPathname());

            return true;
        };

        $this->reader->expects($this->once())->method('read')
            ->with($this->callback($importFileAssertion))->willReturn($contentFromFile);

        $this->parentProcessor->expects($this->once())->method('process')
            ->with($contentFromFile, $this->callback($importFileAssertion))
            ->willReturn($contentModifiedByParentProcess);

        $this->processor->setParent($this->parentProcessor);

        $processed = $this->processor->process($contentToProcess, $file);

        $this->assertEquals($result, $processed);
    }
}
