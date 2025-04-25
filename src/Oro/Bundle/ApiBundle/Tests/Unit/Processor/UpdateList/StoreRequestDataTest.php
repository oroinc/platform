<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Processor\UpdateList\StoreRequestData;
use Oro\Bundle\GaufretteBundle\FileManager;
use PHPUnit\Framework\MockObject\MockObject;

class StoreRequestDataTest extends UpdateListProcessorTestCase
{
    private FileManager&MockObject $fileManager;
    private StoreRequestData $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->fileManager = $this->createMock(FileManager::class);
        $this->processor = new StoreRequestData($this->fileManager);
    }

    public function testProcessWithoutFileName(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The target file name was not set to the context.');

        $this->fileManager->expects(self::never())
            ->method('writeStreamToStorage');

        $resource = fopen(__DIR__ . '/../../Fixtures/Entity/User.php', 'rb');
        try {
            $this->context->setRequestData($resource);
            $this->processor->process($this->context);
        } finally {
            fclose($resource);
        }
    }

    public function testProcessWithoutRequestData(): void
    {
        $this->fileManager->expects(self::never())
            ->method('writeStreamToStorage');

        $this->context->setTargetFileName('test.data');
        $this->processor->process($this->context);
    }

    public function testProcessOnEmptyRequestDataResource(): void
    {
        $this->fileManager->expects(self::once())
            ->method('writeStreamToStorage')
            ->willReturn(false);

        $resource = fopen(__DIR__ . '/../../Fixtures/Entity/User.php', 'rb');
        try {
            $this->context->setTargetFileName('test.data');
            $this->context->setRequestData($resource);
            $this->processor->process($this->context);
        } finally {
            fclose($resource);
        }

        self::assertNull($this->context->getRequestData());
        $errors = $this->context->getErrors();
        self::assertNull($this->context->getTargetFileName());
        self::assertCount(1, $errors);
        self::assertEquals('The request data should not be empty.', $errors[0]->getDetail());
        self::assertEquals(400, $errors[0]->getStatusCode());
    }

    public function testProcessOnRequestDataResource(): void
    {
        $targetFileName = 'test.data';

        $this->fileManager->expects(self::once())
            ->method('writeStreamToStorage')
            ->willReturn(true);

        $resource = fopen(__DIR__ . '/../../Fixtures/Entity/User.php', 'rb');
        try {
            $this->context->setTargetFileName($targetFileName);
            $this->context->setRequestData($resource);
            $this->processor->process($this->context);
        } finally {
            fclose($resource);
        }

        self::assertSame($targetFileName, $this->context->getTargetFileName());
        self::assertNull($this->context->getRequestData());
        self::assertEmpty($this->context->getErrors());
    }

    public function testProcessOnEmptyRequestDataArray(): void
    {
        $this->fileManager->expects(self::never())
            ->method('writeToStorage');

        $this->context->setTargetFileName('test.data');
        $this->context->setRequestData([]);
        $this->processor->process($this->context);

        self::assertNull($this->context->getRequestData());
        $errors = $this->context->getErrors();
        self::assertNull($this->context->getTargetFileName());
        self::assertCount(1, $errors);
        self::assertEquals('The request data should not be empty.', $errors[0]->getDetail());
        self::assertEquals(400, $errors[0]->getStatusCode());
    }

    public function testProcessOnRequestDataArray(): void
    {
        $targetFileName = 'test.data';

        $this->fileManager->expects(self::once())
            ->method('writeToStorage');

        $this->context->setTargetFileName($targetFileName);
        $this->context->setRequestData([['field' => 'value']]);
        $this->processor->process($this->context);

        self::assertSame($targetFileName, $this->context->getTargetFileName());
        self::assertNull($this->context->getRequestData());
        self::assertEmpty($this->context->getErrors());
    }
}
