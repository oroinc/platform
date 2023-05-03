<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Handler;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;
use Oro\Bundle\ApiBundle\Batch\Processor\BatchUpdateItemProcessor;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Component\ChainProcessor\ProcessorBagInterface;

class BatchUpdateItemTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|BatchUpdateItemProcessor */
    private $processor;

    /** @var BatchUpdateContext */
    private $updateContext;

    /** @var BatchUpdateItem */
    private $batchUpdateItem;

    protected function setUp(): void
    {
        $this->processor = $this->getMockBuilder(BatchUpdateItemProcessor::class)
            ->onlyMethods(['process'])
            ->setConstructorArgs([$this->createMock(ProcessorBagInterface::class), 'batch_update_item'])
            ->getMock();
        $this->updateContext = new BatchUpdateContext();
        $this->updateContext->setVersion('1.2');
        $this->updateContext->getRequestType()->add('test');
        $this->updateContext->setSupportedEntityClasses(['Test\Entity']);

        $this->batchUpdateItem = new BatchUpdateItem(
            123,
            $this->processor,
            $this->updateContext
        );
    }

    public function testGetIndex()
    {
        self::assertSame(123, $this->batchUpdateItem->getIndex());
    }

    public function testGetContext()
    {
        $context = $this->batchUpdateItem->getContext();
        self::assertEquals($this->updateContext->getVersion(), $context->getVersion());
        self::assertEquals($this->updateContext->getRequestType(), $context->getRequestType());
        self::assertSame($this->updateContext->getSharedData(), $context->getSharedData());
        self::assertSame($this->updateContext->getSummary(), $context->getSummary());
        self::assertEquals($this->updateContext->getSupportedEntityClasses(), $context->getSupportedEntityClasses());

        // test that the created context is stored in the memory
        self::assertSame($context, $this->batchUpdateItem->getContext());
    }

    public function testGetIncludedData()
    {
        $includedData = $this->createMock(IncludedData::class);
        $this->updateContext->setIncludedData($includedData);
        self::assertSame($includedData, $this->batchUpdateItem->getIncludedData());
    }

    public function testGetIncludedDataWhenIncludedDataNotExist()
    {
        self::assertNull($this->batchUpdateItem->getIncludedData());
    }

    public function testInitialize()
    {
        $data = ['test'];

        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (BatchUpdateItemContext $context) use ($data) {
                self::assertSame($data, $context->getRequestData());
                self::assertEquals(ApiActionGroup::INITIALIZE, $context->getFirstGroup());
                self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
            });

        $this->batchUpdateItem->initialize($data);
    }

    public function testTransformWhenNoErrorsOccurred()
    {
        $result = ['test'];

        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (BatchUpdateItemContext $context) use ($result) {
                self::assertEquals(ApiActionGroup::TRANSFORM_DATA, $context->getFirstGroup());
                self::assertEquals(ApiActionGroup::TRANSFORM_DATA, $context->getLastGroup());
                $context->setResult($result);
            });

        $this->batchUpdateItem->transform();
        self::assertSame($result, $this->batchUpdateItem->getContext()->getResult());
    }

    public function testTransformWhenSomeErrorsOccurred()
    {
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (BatchUpdateItemContext $context) {
                self::assertEquals(ApiActionGroup::TRANSFORM_DATA, $context->getFirstGroup());
                self::assertEquals(ApiActionGroup::TRANSFORM_DATA, $context->getLastGroup());
                $context->setResult(['test']);
                $context->addError(Error::create('some error'));
            });

        $this->batchUpdateItem->transform();
        self::assertFalse($this->batchUpdateItem->getContext()->hasResult());
    }
}
