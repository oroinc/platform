<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Processor\UpdateList\StoreRequestData;
use Oro\Bundle\GaufretteBundle\FileManager;

class StoreRequestDataTest extends UpdateListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FileManager */
    private $fileManager;

    /** @var StoreRequestData */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileManager = $this->createMock(FileManager::class);
        $this->processor = new StoreRequestData($this->fileManager);
    }

    public function testProcessWithoutResource()
    {
        $this->fileManager->expects(self::never())
            ->method('writeStreamToStorage');

        $this->processor->process($this->context);
    }

    public function testProcessOnNonResource()
    {
        $this->fileManager->expects(self::never())
            ->method('writeStreamToStorage');

        $this->context->setRequestData('test');
        $this->processor->process($this->context);
    }

    public function testProcessWithoutFileName()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The target file name was not set to the context.');

        $resource = fopen(__DIR__ . '/../../Fixtures/Entity/User.php', 'rb');

        $this->fileManager->expects(self::never())
            ->method('writeStreamToStorage');

        $this->context->setRequestData($resource);
        $this->processor->process($this->context);
    }

    public function testProcessOnEmptyRequestData()
    {
        $resource = fopen(__DIR__ . '/../../Fixtures/Entity/User.php', 'rb');

        $this->fileManager->expects(self::once())
            ->method('writeStreamToStorage')
            ->willReturn(false);

        $this->context->setRequestData($resource);
        $this->context->setTargetFileName('test.data');
        $this->processor->process($this->context);

        self::assertNull($this->context->getRequestData());
        $errors = $this->context->getErrors();
        self::assertNull($this->context->getTargetFileName());
        self::assertCount(1, $errors);
        self::assertEquals('The request data should not be empty', $errors[0]->getDetail());
        self::assertEquals(400, $errors[0]->getStatusCode());
    }

    public function testProcess()
    {
        $targetFileName = 'test.data';
        $resource = fopen(__DIR__ . '/../../Fixtures/Entity/User.php', 'rb');

        $this->fileManager->expects(self::once())
            ->method('writeStreamToStorage')
            ->willReturn(true);

        $this->context->setRequestData($resource);
        $this->context->setTargetFileName($targetFileName);
        $this->processor->process($this->context);

        self::assertSame($targetFileName, $this->context->getTargetFileName());
        self::assertNull($this->context->getRequestData());
        self::assertEmpty($this->context->getErrors());
    }
}
