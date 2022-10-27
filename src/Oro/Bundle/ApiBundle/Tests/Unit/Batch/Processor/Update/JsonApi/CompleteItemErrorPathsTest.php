<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update\JsonApi;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\JsonApi\CompleteItemErrorPaths;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update\BatchUpdateProcessorTestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CompleteItemErrorPathsTest extends BatchUpdateProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var CompleteItemErrorPaths */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->processor = new CompleteItemErrorPaths($this->logger);
    }

    public function testProcessWithoutBatchItems()
    {
        $this->context->setFile(new ChunkFile('test', 10, 100));
        $this->processor->process($this->context);
    }

    public function testProcessWithBatchItemWithoutErrors()
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([]);

        $this->context->setFile(new ChunkFile('test', 10, 100, 'data'));
        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWithBatchItemWithErrorWithoutSource()
    {
        $error = new Error();

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error]);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::never())
            ->method('getIncludedData');

        $this->context->setFile(new ChunkFile('test', 10, 100, 'data'));
        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);

        self::assertNotNull($error->getSource());
        self::assertEquals('/data/100', $error->getSource()->getPointer());
    }

    public function testProcessWithBatchItemWithErrorWithoutPointer()
    {
        $error = new Error();
        $error->setSource(new ErrorSource());

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error]);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::never())
            ->method('getIncludedData');

        $this->context->setFile(new ChunkFile('test', 10, 100, 'data'));
        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);

        self::assertEquals('/data/100', $error->getSource()->getPointer());
    }

    public function testProcessWithBatchItemWithErrorWithPointer()
    {
        $error = new Error();
        $error->setSource(new ErrorSource());
        $error->getSource()->setPointer('/data/attributes/name');

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error]);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::never())
            ->method('getIncludedData');

        $this->context->setFile(new ChunkFile('test', 10, 100, 'data'));
        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);

        self::assertEquals('/data/100/attributes/name', $error->getSource()->getPointer());
    }

    public function testProcessWithBatchItemWithErrorWithPointerFromUnknownSectionAndNoIncludedData()
    {
        $error = new Error();
        $error->setSource(new ErrorSource());
        $error->getSource()->setPointer('/unknown/attributes/name');

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error]);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::once())
            ->method('getIncludedData')
            ->willReturn(null);

        $this->context->setFile(new ChunkFile('test', 10, 100, 'data'));
        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);

        self::assertEquals('/data/100/unknown/attributes/name', $error->getSource()->getPointer());
    }

    public function testProcessWithBatchItemWithErrorWithPointerFromUnknownSectionAndHasIncludedData()
    {
        $error = new Error();
        $error->setSource(new ErrorSource());
        $error->getSource()->setPointer('/unknown/attributes/name');

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $includedData = $this->createMock(IncludedData::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error]);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::once())
            ->method('getIncludedData')
            ->willReturn(null);
        $includedData->expects(self::never())
            ->method('getIncludedItemIndex');

        $this->context->setFile(new ChunkFile('test', 10, 100, 'data'));
        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);

        self::assertEquals('/data/100/unknown/attributes/name', $error->getSource()->getPointer());
    }

    public function testProcessWithBatchItemWithSeveralErrors()
    {
        $error1 = new Error();
        $error1->setSource(new ErrorSource());
        $error1->getSource()->setPointer('/data/attributes/name');
        $error2 = new Error();
        $error2->setSource(new ErrorSource());
        $error2->getSource()->setPointer('/data/attributes/another');

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error1, $error2]);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::never())
            ->method('getIncludedData');

        $this->context->setFile(new ChunkFile('test', 10, 100, 'data'));
        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);

        self::assertEquals('/data/100/attributes/name', $error1->getSource()->getPointer());
        self::assertEquals('/data/100/attributes/another', $error2->getSource()->getPointer());
    }

    public function testProcessWithSeveralBatchItemWithErrors()
    {
        $error1 = new Error();
        $error1->setSource(new ErrorSource());
        $error1->getSource()->setPointer('/data/attributes/name');
        $error2 = new Error();
        $error2->setSource(new ErrorSource());
        $error2->getSource()->setPointer('/data/attributes/name');

        $item1 = $this->createMock(BatchUpdateItem::class);
        $itemContext1 = $this->createMock(BatchUpdateItemContext::class);
        $item1->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext1);
        $itemContext1->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error1]);
        $item1->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item1->expects(self::never())
            ->method('getIncludedData');
        $item2 = $this->createMock(BatchUpdateItem::class);
        $itemContext2 = $this->createMock(BatchUpdateItemContext::class);
        $item2->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext2);
        $itemContext2->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error2]);
        $item2->expects(self::once())
            ->method('getIndex')
            ->willReturn(1);
        $item2->expects(self::never())
            ->method('getIncludedData');

        $this->context->setFile(new ChunkFile('test', 10, 100, 'data'));
        $this->context->setBatchItems([$item1, $item2]);
        $this->processor->process($this->context);

        self::assertEquals('/data/100/attributes/name', $error1->getSource()->getPointer());
        self::assertEquals('/data/101/attributes/name', $error2->getSource()->getPointer());
    }

    public function testProcessWithBatchItemUnexpectedErrorInIncludedRoot()
    {
        $error1 = new Error();
        $error1->setSource(new ErrorSource());
        $error1->getSource()->setPointer('/included');

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error1]);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);

        $this->context->setFile(new ChunkFile('test', 10, 100, 'data'));
        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);

        self::assertEquals('/included', $error1->getSource()->getPointer());
    }

    public function testProcessWithBatchItemUnexpectedErrorInIncludedData()
    {
        $error1 = new Error();
        $error1->setSource(new ErrorSource());
        $error1->getSource()->setPointer('/included/0');

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $includedData = $this->createMock(IncludedData::class);
        $item->expects(self::exactly(2))
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error1]);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::once())
            ->method('getIncludedData')
            ->willReturn($includedData);
        $itemContext->expects(self::once())
            ->method('getRequestData')
            ->willReturn([
                'data'     => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => ['data' => ['type' => 'contacts', 'id' => '2']]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'contacts',
                        'id'         => '2',
                        'attributes' => ['name' => 'Contact 2']
                    ]
                ]
            ]);
        $includedData->expects(self::once())
            ->method('getIncludedItemIndex')
            ->with('contacts', '2')
            ->willReturn(10);

        $this->context->setFile(new ChunkFile('test', 10, 100, 'data'));
        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);

        self::assertEquals('/included/10', $error1->getSource()->getPointer());
    }

    public function testProcessWithBatchItemValidationErrorInIncludedData()
    {
        $error1 = new Error();
        $error1->setSource(new ErrorSource());
        $error1->getSource()->setPointer('/included/0/attributes/name');

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $includedData = $this->createMock(IncludedData::class);
        $item->expects(self::exactly(2))
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error1]);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::once())
            ->method('getIncludedData')
            ->willReturn($includedData);
        $itemContext->expects(self::once())
            ->method('getRequestData')
            ->willReturn([
                'data'     => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => ['data' => ['type' => 'contacts', 'id' => '2']]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'contacts',
                        'id'         => '2',
                        'attributes' => ['name' => 'Contact 2']
                    ]
                ]
            ]);
        $includedData->expects(self::once())
            ->method('getIncludedItemIndex')
            ->with('contacts', '2')
            ->willReturn(10);

        $this->context->setFile(new ChunkFile('test', 10, 100, 'data'));
        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);

        self::assertEquals('/included/10/attributes/name', $error1->getSource()->getPointer());
    }

    public function testProcessWithBatchItemValidationErrorInIncludedDataButIncludedItemNotFound()
    {
        $error1 = new Error();
        $error1->setSource(new ErrorSource());
        $error1->getSource()->setPointer('/included/1/attributes/name');

        $requestData = [
            'data'     => [
                'type'          => 'accounts',
                'relationships' => [
                    'contacts' => [
                        'data' => [
                            ['type' => 'contacts', 'id' => '2'],
                            ['type' => 'contacts', 'id' => '3']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => 'contacts',
                    'id'         => '2',
                    'attributes' => ['name' => 'Contact 2']
                ]
            ]
        ];

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $includedData = $this->createMock(IncludedData::class);
        $item->expects(self::exactly(2))
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error1]);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::once())
            ->method('getIncludedData')
            ->willReturn($includedData);
        $itemContext->expects(self::once())
            ->method('getRequestData')
            ->willReturn($requestData);
        $includedData->expects(self::never())
            ->method('getIncludedItemIndex');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to compute a correct pointer for an included item'
                . ' because the item does not exist in a request data.',
                self::identicalTo([
                    'requestData' => $requestData,
                    'itemIndex'   => 1
                ])
            );

        $this->context->setFile(new ChunkFile('test', 10, 100, 'data'));
        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);

        self::assertEquals('/data/100', $error1->getSource()->getPointer());
    }

    public function testProcessWithBatchItemValidationErrorInIncludedDataButIncludedItemIndexNotFound()
    {
        $error1 = new Error();
        $error1->setSource(new ErrorSource());
        $error1->getSource()->setPointer('/included/0/attributes/name');

        $requestData = [
            'data'     => [
                'type'          => 'accounts',
                'relationships' => [
                    'contact' => ['data' => ['type' => 'contacts', 'id' => '2']]
                ]
            ],
            'included' => [
                [
                    'type'       => 'contacts',
                    'id'         => '2',
                    'attributes' => ['name' => 'Contact 2']
                ]
            ]
        ];

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $includedData = $this->createMock(IncludedData::class);
        $item->expects(self::exactly(2))
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error1]);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::once())
            ->method('getIncludedData')
            ->willReturn($includedData);
        $itemContext->expects(self::once())
            ->method('getRequestData')
            ->willReturn($requestData);
        $includedData->expects(self::once())
            ->method('getIncludedItemIndex')
            ->with('contacts', '2')
            ->willReturn(null);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to compute a correct pointer for an included item'
                . ' because the item cannot be found in the included item index.',
                self::identicalTo([
                    'requestData' => $requestData,
                    'itemType'    => 'contacts',
                    'itemId'      => '2'
                ])
            );

        $this->context->setFile(new ChunkFile('test', 10, 100, 'data'));
        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);

        self::assertEquals('/data/100', $error1->getSource()->getPointer());
    }
}
