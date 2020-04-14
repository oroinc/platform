<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Processor\UpdateList\DeleteTargetFileIfAsyncOperationNotStarted;
use Oro\Bundle\ApiBundle\Processor\UpdateList\StartAsyncOperation;
use Oro\Bundle\GaufretteBundle\FileManager;

class DeleteTargetFileIfAsyncOperationNotStartedTest extends UpdateListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FileManager */
    private $fileManager;

    /** @var DeleteTargetFileIfAsyncOperationNotStarted */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileManager = $this->createMock(FileManager::class);
        $this->processor = new DeleteTargetFileIfAsyncOperationNotStarted($this->fileManager);
    }

    public function testProcessWhenNoTargetFileName()
    {
        $this->fileManager->expects(self::never())
            ->method('hasFile');
        $this->fileManager->expects(self::never())
            ->method('deleteFile');

        $this->processor->process($this->context);
    }

    public function testProcessWhenAsyncOperationStarted()
    {
        $targetFileName = 'test.data';

        $this->fileManager->expects(self::never())
            ->method('hasFile');
        $this->fileManager->expects(self::never())
            ->method('deleteFile');

        $this->context->setTargetFileName($targetFileName);
        $this->context->setProcessed(StartAsyncOperation::OPERATION_NAME);
        $this->processor->process($this->context);

        self::assertSame($targetFileName, $this->context->getTargetFileName());
    }

    public function testProcessWhenAsyncOperationNotStartedButTargetFileDoesNotExist()
    {
        $targetFileName = 'test.data';

        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with($targetFileName)
            ->willReturn(false);
        $this->fileManager->expects(self::never())
            ->method('deleteFile');

        $this->context->setTargetFileName($targetFileName);
        $this->processor->process($this->context);

        self::assertNull($this->context->getTargetFileName());
    }

    public function testProcessWhenAsyncOperationNotStartedAndTargetFileExists()
    {
        $targetFileName = 'test.data';

        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with($targetFileName)
            ->willReturn(true);
        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with($targetFileName);

        $this->context->setTargetFileName($targetFileName);
        $this->processor->process($this->context);

        self::assertNull($this->context->getTargetFileName());
    }
}
