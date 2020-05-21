<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Processor\UpdateList\GenerateTargetFileName;

class GenerateTargetFileNameTest extends UpdateListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FileNameProvider */
    private $fileNameProvider;

    /** @var GenerateTargetFileName */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileNameProvider = $this->createMock(FileNameProvider::class);
        $this->processor = new GenerateTargetFileName($this->fileNameProvider);
    }

    public function testProcessOnExistingFileName()
    {
        $fileName = 'test.txt';

        $this->fileNameProvider->expects(self::never())
            ->method('getDataFileName');

        $this->context->setTargetFileName($fileName);
        $this->processor->process($this->context);
        self::assertEquals($fileName, $this->context->getTargetFileName());
    }

    public function testProcess()
    {
        $fileName = 'test.txt';

        $this->fileNameProvider->expects(self::once())
            ->method('getDataFileName')
            ->willReturn($fileName);

        $this->processor->process($this->context);
        self::assertEquals($fileName, $this->context->getTargetFileName());
    }
}
