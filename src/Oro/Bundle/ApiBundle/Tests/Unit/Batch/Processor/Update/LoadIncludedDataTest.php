<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\IncludeAccessorInterface;
use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\IncludeAccessorRegistry;
use Oro\Bundle\ApiBundle\Batch\IncludeMapManager;
use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\LoadIncludedData;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\GaufretteBundle\FileManager;

class LoadIncludedDataTest extends BatchUpdateProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|IncludeAccessorRegistry */
    private $includeAccessorRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|IncludeMapManager */
    private $includeMapManager;

    /** @var LoadIncludedData */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->includeAccessorRegistry = $this->createMock(IncludeAccessorRegistry::class);
        $this->includeMapManager = $this->createMock(IncludeMapManager::class);

        $this->processor = new LoadIncludedData($this->includeAccessorRegistry, $this->includeMapManager);
    }

    public function testProcessWhenIncludedDataAlreadyLoaded()
    {
        $this->includeAccessorRegistry->expects(self::never())
            ->method('getAccessor');

        $this->context->setProcessed(LoadIncludedData::OPERATION_NAME);
        $this->context->setResult([['data' => ['type' => 'accounts']]]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenPrimaryDataWereNotLoaded()
    {
        $this->includeAccessorRegistry->expects(self::never())
            ->method('getAccessor');

        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(LoadIncludedData::OPERATION_NAME));
    }

    public function testProcessWhenIncludeAccessorNotFound()
    {
        $this->includeAccessorRegistry->expects(self::once())
            ->method('getAccessor')
            ->with(self::identicalTo($this->context->getRequestType()))
            ->willReturn(null);

        $this->context->setResult([['data' => ['type' => 'accounts']]]);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(LoadIncludedData::OPERATION_NAME));
        self::assertNull($this->context->getIncludedData());
        self::assertFalse($this->context->isRetryAgain());
        self::assertFalse($this->context->hasSkippedGroups());
    }

    public function testProcessWhenIncludedDataSuccessfullyLoaded()
    {
        $operationId = 123;
        $data = [['data' => ['type' => 'accounts']]];
        $includedData = $this->createMock(IncludedData::class);
        $fileManager = $this->createMock(FileManager::class);
        $includeAccessor = $this->createMock(IncludeAccessorInterface::class);

        $this->includeAccessorRegistry->expects(self::once())
            ->method('getAccessor')
            ->with(self::identicalTo($this->context->getRequestType()))
            ->willReturn($includeAccessor);
        $this->includeMapManager->expects(self::once())
            ->method('getIncludedItems')
            ->with(
                self::identicalTo($fileManager),
                $operationId,
                self::identicalTo($includeAccessor),
                $data
            )
            ->willReturn($includedData);

        $this->context->setOperationId($operationId);
        $this->context->setFileManager($fileManager);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(LoadIncludedData::OPERATION_NAME));
        self::assertSame($includedData, $this->context->getIncludedData());
        self::assertFalse($this->context->isRetryAgain());
        self::assertFalse($this->context->hasSkippedGroups());
    }

    public function testProcessWhenIncludedDataCannotBeLoaded()
    {
        $operationId = 123;
        $data = [['data' => ['type' => 'accounts']]];
        $fileManager = $this->createMock(FileManager::class);
        $includeAccessor = $this->createMock(IncludeAccessorInterface::class);

        $this->includeAccessorRegistry->expects(self::once())
            ->method('getAccessor')
            ->with(self::identicalTo($this->context->getRequestType()))
            ->willReturn($includeAccessor);
        $this->includeMapManager->expects(self::once())
            ->method('getIncludedItems')
            ->with(
                self::identicalTo($fileManager),
                $operationId,
                self::identicalTo($includeAccessor),
                $data
            )
            ->willReturn(null);

        $this->context->setOperationId($operationId);
        $this->context->setFileManager($fileManager);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(LoadIncludedData::OPERATION_NAME));
        self::assertNull($this->context->getIncludedData());
        self::assertTrue($this->context->isRetryAgain());
        self::assertEquals(
            'Not possible to get included items now because the lock for the include index cannot be acquired.',
            $this->context->getRetryReason()
        );
        self::assertEquals([ApiActionGroup::INITIALIZE], $this->context->getSkippedGroups());
    }
}
