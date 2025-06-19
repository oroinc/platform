<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Processor\UpdateList\GenerateTargetFileName;
use PHPUnit\Framework\MockObject\MockObject;

class GenerateTargetFileNameTest extends UpdateListProcessorTestCase
{
    private FileNameProvider&MockObject $fileNameProvider;
    private GenerateTargetFileName $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->fileNameProvider = $this->createMock(FileNameProvider::class);
        $this->processor = new GenerateTargetFileName($this->fileNameProvider);
    }

    public function testProcessOnExistingFileName(): void
    {
        $fileName = 'test.txt';

        $this->fileNameProvider->expects(self::never())
            ->method('getDataFileName');

        $this->context->setTargetFileName($fileName);
        $this->processor->process($this->context);
        self::assertEquals($fileName, $this->context->getTargetFileName());
    }

    public function testProcess(): void
    {
        $fileName = 'test.txt';

        $this->fileNameProvider->expects(self::once())
            ->method('getDataFileName')
            ->willReturn($fileName);

        $this->processor->process($this->context);
        self::assertEquals($fileName, $this->context->getTargetFileName());
    }
}
