<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer\ComplexData;

use Oro\Bundle\ApiBundle\Async\Topic\DeleteAsyncOperationTopic;
use Oro\Bundle\ApiBundle\Batch\ErrorManager;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\UpdateList\UpdateListContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\BatchApiToImportErrorConverterInterface;
use Oro\Bundle\ImportExportBundle\Writer\ComplexData\JsonApiBatchApiImportWriter;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JsonApiBatchApiImportWriterTest extends TestCase
{
    private const string ENTITY_CLASS = 'Test\Entity';
    private const string REQUEST_TYPE = 'test_request_type';

    private ContextRegistry&MockObject $contextRegistry;
    private ActionProcessorBagInterface&MockObject $actionProcessorBag;
    private FileManager&MockObject $fileManager;
    private ErrorManager&MockObject $errorManager;
    private BatchApiToImportErrorConverterInterface&MockObject $errorConverter;
    private MessageProducerInterface&MockObject $producer;
    private StepExecution&MockObject $stepExecution;
    private JsonApiBatchApiImportWriter $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->contextRegistry = $this->createMock(ContextRegistry::class);
        $this->actionProcessorBag = $this->createMock(ActionProcessorBagInterface::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->errorConverter = $this->createMock(BatchApiToImportErrorConverterInterface::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->stepExecution = $this->createMock(StepExecution::class);

        $this->writer = new JsonApiBatchApiImportWriter(
            $this->contextRegistry,
            $this->actionProcessorBag,
            $this->fileManager,
            $this->errorManager,
            $this->errorConverter,
            $this->producer,
            self::ENTITY_CLASS,
            self::REQUEST_TYPE
        );
        $this->writer->setStepExecution($this->stepExecution);
    }

    private function getApiContext(): UpdateListContext
    {
        return new UpdateListContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testWriteSuccessfullyFinished(): void
    {
        $items = [
            [
                'data' => ['type' => 'entity_type', 'id' => 'entity_1'],
                'included' => [['type' => 'included_entity', 'id' => 'included_entity_1']]
            ],
            [
                'data' => ['type' => 'entity_type', 'id' => 'entity_2']
            ],
            [
                'data' => ['type' => 'entity_type', 'id' => 'entity_3'],
                'included' => [['type' => 'included_entity', 'id' => 'included_entity_3']]
            ]
        ];
        $requestData = [
            'data' => [
                ['type' => 'entity_type', 'id' => 'entity_1'],
                ['type' => 'entity_type', 'id' => 'entity_2'],
                ['type' => 'entity_type', 'id' => 'entity_3']
            ],
            'included' => [
                ['type' => 'included_entity', 'id' => 'included_entity_1'],
                ['type' => 'included_entity', 'id' => 'included_entity_3']
            ]
        ];
        $operationId = 123;
        $apiResult = [
            'data' => [
                'type' => 'asyncoperations',
                'id' => (string)$operationId,
                'attributes' => [
                    'status' => AsyncOperation::STATUS_SUCCESS,
                    'summary' => [
                        'createCount' => 1,
                        'updateCount' => 2,
                        'errorCount' => 0
                    ]
                ]
            ]
        ];

        $apiProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->actionProcessorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiAction::UPDATE_LIST)
            ->willReturn($apiProcessor);
        $apiContext = $this->getApiContext();
        $apiProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($apiContext);

        $apiProcessor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($apiContext))
            ->willReturnCallback(function (UpdateListContext $apiContext) use ($requestData, $operationId, $apiResult) {
                self::assertEquals(
                    new RequestType([RequestType::REST, RequestType::JSON_API, self::REQUEST_TYPE]),
                    $apiContext->getRequestType()
                );
                self::assertTrue($apiContext->isMainRequest());
                self::assertEquals(self::ENTITY_CLASS, $apiContext->getClassName());
                self::assertEquals($requestData, $apiContext->getRequestData());
                self::assertFalse($apiContext->isProcessByMessageQueue());

                $apiContext->setOperationId($operationId);
                $apiContext->setResult($apiResult);
            });

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->with(self::identicalTo($this->stepExecution))
            ->willReturn($context);
        $context->expects(self::once())
            ->method('incrementAddCount')
            ->with($apiResult['data']['attributes']['summary']['createCount']);
        $context->expects(self::once())
            ->method('incrementUpdateCount')
            ->with($apiResult['data']['attributes']['summary']['updateCount']);
        $context->expects(self::never())
            ->method('incrementErrorEntriesCount');

        $this->errorManager->expects(self::never())
            ->method('readErrors');
        $this->errorConverter->expects(self::never())
            ->method('convertToImportError');
        $context->expects(self::never())
            ->method('addError');

        $this->producer->expects(self::once())
            ->method('send')
            ->with(DeleteAsyncOperationTopic::getName(), ['operationId' => $operationId]);

        $this->writer->write($items);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testWriteFinishedWithErrors(): void
    {
        $items = [
            [
                'data' => ['type' => 'entity_type', 'id' => 'entity_1'],
                'included' => [['type' => 'included_entity', 'id' => 'included_entity_1']]
            ]
        ];
        $requestData = [
            'data' => [
                ['type' => 'entity_type', 'id' => 'entity_1']
            ],
            'included' => [
                ['type' => 'included_entity', 'id' => 'included_entity_1']
            ]
        ];
        $operationId = 123;
        $apiResult = [
            'data' => [
                'type' => 'asyncoperations',
                'id' => (string)$operationId,
                'attributes' => [
                    'status' => AsyncOperation::STATUS_FAILED,
                    'summary' => [
                        'createCount' => 0,
                        'updateCount' => 0,
                        'errorCount' => 1
                    ]
                ]
            ]
        ];

        $apiProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->actionProcessorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiAction::UPDATE_LIST)
            ->willReturn($apiProcessor);
        $apiContext = $this->getApiContext();
        $apiProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($apiContext);

        $apiProcessor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($apiContext))
            ->willReturnCallback(function (UpdateListContext $apiContext) use ($requestData, $operationId, $apiResult) {
                self::assertEquals(
                    new RequestType([RequestType::REST, RequestType::JSON_API, self::REQUEST_TYPE]),
                    $apiContext->getRequestType()
                );
                self::assertTrue($apiContext->isMainRequest());
                self::assertEquals(self::ENTITY_CLASS, $apiContext->getClassName());
                self::assertEquals($requestData, $apiContext->getRequestData());
                self::assertFalse($apiContext->isProcessByMessageQueue());

                $apiContext->setOperationId($operationId);
                $apiContext->setResult($apiResult);
            });

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->with(self::identicalTo($this->stepExecution))
            ->willReturn($context);
        $context->expects(self::once())
            ->method('incrementAddCount')
            ->with(0);
        $context->expects(self::once())
            ->method('incrementUpdateCount')
            ->with(0);
        $context->expects(self::once())
            ->method('incrementErrorEntriesCount')
            ->with(2);

        $error1 = BatchError::createValidationError('some error', 'Some Error 1')->setItemIndex(0);
        $error2 = BatchError::createValidationError('some error', 'Some Error 2')->setItemIndex(0);
        $this->errorManager->expects(self::once())
            ->method('readErrors')
            ->with(self::identicalTo($this->fileManager), $operationId, 0, \PHP_INT_MAX)
            ->willReturn([$error1, $error2, $error1]);
        $this->errorConverter->expects(self::exactly(3))
            ->method('convertToImportError')
            ->with(self::isInstanceOf(BatchError::class), $requestData)
            ->willReturnCallback(function (BatchError $error) {
                return 'Import: ' . $error->getDetail();
            });
        $context->expects(self::exactly(2))
            ->method('addError')
            ->withConsecutive(
                ['Import: Some Error 1'],
                ['Import: Some Error 2']
            );

        $this->producer->expects(self::once())
            ->method('send')
            ->with(DeleteAsyncOperationTopic::getName(), ['operationId' => $operationId]);

        $this->writer->write($items);
    }

    public function testWriteFinishedWithErrorsHappenedBeforeAsyncOperationIsCreated(): void
    {
        $items = [
            [
                'data' => ['type' => 'entity_type', 'id' => 'entity_1'],
                'included' => [['type' => 'included_entity', 'id' => 'included_entity_1']]
            ]
        ];
        $requestData = [
            'data' => [
                ['type' => 'entity_type', 'id' => 'entity_1']
            ],
            'included' => [
                ['type' => 'included_entity', 'id' => 'included_entity_1']
            ]
        ];
        $apiResult = [
            'errors' => [
                ['title' => 'first error', 'detail' => 'First Error.'],
                ['title' => 'second error'],
                ['title' => 'third error', 'detail' => '']
            ]
        ];

        $apiProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->actionProcessorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiAction::UPDATE_LIST)
            ->willReturn($apiProcessor);
        $apiContext = $this->getApiContext();
        $apiProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($apiContext);

        $apiProcessor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($apiContext))
            ->willReturnCallback(function (UpdateListContext $apiContext) use ($requestData, $apiResult) {
                self::assertEquals(
                    new RequestType([RequestType::REST, RequestType::JSON_API, self::REQUEST_TYPE]),
                    $apiContext->getRequestType()
                );
                self::assertTrue($apiContext->isMainRequest());
                self::assertEquals(self::ENTITY_CLASS, $apiContext->getClassName());
                self::assertEquals($requestData, $apiContext->getRequestData());
                self::assertFalse($apiContext->isProcessByMessageQueue());

                $apiContext->setResult($apiResult);
            });

        $this->contextRegistry->expects(self::never())
            ->method('getByStepExecution');

        $this->errorManager->expects(self::never())
            ->method('readErrors');
        $this->errorConverter->expects(self::never())
            ->method('convertToImportError');
        $this->stepExecution->expects(self::once())
            ->method('addError')
            ->with('The import failed. Reason: First Error. second error. third error.');

        $this->producer->expects(self::never())
            ->method('send');

        $this->writer->write($items);
    }

    public function testWriteWhenAllItemsInRequestDataHaveErrors(): void
    {
        $items = [
            [
                'data' => ['type' => 'entity_type', 'id' => 'entity_1'],
                'included' => [['type' => 'included_entity', 'id' => 'included_entity_1']],
                'errors' => [
                    [
                        'title' => 'request data constraint',
                        'detail' => 'item 1 - error 1',
                        'source' => ['pointer' => '/data']
                    ],
                    [
                        'title' => 'request data constraint',
                        'detail' => 'item 1 - error 2',
                        'source' => ['pointer' => '/include/0']
                    ]
                ]
            ],
            [
                'data' => ['type' => 'entity_type', 'id' => 'entity_2', 'attributes' => ['name' => 'Item 2']],
                'errors' => [
                    [
                        'title' => 'request data constraint',
                        'detail' => 'item 2 - error 1',
                        'source' => ['pointer' => '/data/attributes/name']
                    ]
                ]
            ]
        ];
        $requestData = [
            'data' => [
                ['type' => 'entity_type', 'id' => 'entity_1'],
                ['type' => 'entity_type', 'id' => 'entity_2', 'attributes' => ['name' => 'Item 2']]
            ],
            'included' => [
                ['type' => 'included_entity', 'id' => 'included_entity_1']
            ]
        ];

        $this->actionProcessorBag->expects(self::never())
            ->method('getProcessor');

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->with(self::identicalTo($this->stepExecution))
            ->willReturn($context);
        $context->expects(self::never())
            ->method('incrementAddCount');
        $context->expects(self::never())
            ->method('incrementUpdateCount');
        $context->expects(self::once())
            ->method('incrementErrorEntriesCount')
            ->with(3);

        $this->errorManager->expects(self::never())
            ->method('readErrors');
        $this->errorConverter->expects(self::exactly(3))
            ->method('convertToImportError')
            ->with(self::isInstanceOf(BatchError::class), $requestData)
            ->willReturnCallback(function (BatchError $error) {
                return 'Import: ' . $error->getDetail() . ' | Pointer: ' . $error->getSource()?->getPointer();
            });
        $context->expects(self::exactly(3))
            ->method('addError')
            ->withConsecutive(
                ['Import: item 1 - error 1 | Pointer: /data/0'],
                ['Import: item 1 - error 2 | Pointer: '],
                ['Import: item 2 - error 1 | Pointer: /data/1/attributes/name']
            );

        $this->producer->expects(self::never())
            ->method('send');

        $this->writer->write($items);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testWriteFinishedWithErrorsAndSomeItemsInRequestDataHaveErrors(): void
    {
        $items = [
            [
                'data' => ['type' => 'entity_type', 'id' => 'entity_1', 'attributes' => ['name' => 'Item 1']],
                'included' => [['type' => 'included_entity', 'id' => 'included_entity_1']],
                'errors' => [
                    [
                        'title' => 'request data constraint',
                        'detail' => 'item 1 - error 1',
                        'source' => ['pointer' => '/data/attributes/name']
                    ]
                ]
            ],
            [
                'data' => ['type' => 'entity_type', 'id' => 'entity_2', 'attributes' => ['name' => 'Item 2']]
            ]
        ];
        $requestDataToProcess = [
            'data' => [
                ['type' => 'entity_type', 'id' => 'entity_2', 'attributes' => ['name' => 'Item 2']]
            ]
        ];
        $operationId = 123;
        $apiResult = [
            'data' => [
                'type' => 'asyncoperations',
                'id' => (string)$operationId,
                'attributes' => [
                    'status' => AsyncOperation::STATUS_FAILED,
                    'summary' => [
                        'createCount' => 0,
                        'updateCount' => 0,
                        'errorCount' => 1
                    ]
                ]
            ]
        ];

        $apiProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->actionProcessorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiAction::UPDATE_LIST)
            ->willReturn($apiProcessor);
        $apiContext = $this->getApiContext();
        $apiProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($apiContext);

        $apiProcessor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($apiContext))
            ->willReturnCallback(function (UpdateListContext $apiContext) use (
                $requestDataToProcess,
                $operationId,
                $apiResult
            ) {
                self::assertEquals(
                    new RequestType([RequestType::REST, RequestType::JSON_API, self::REQUEST_TYPE]),
                    $apiContext->getRequestType()
                );
                self::assertTrue($apiContext->isMainRequest());
                self::assertEquals(self::ENTITY_CLASS, $apiContext->getClassName());
                self::assertEquals($requestDataToProcess, $apiContext->getRequestData());
                self::assertFalse($apiContext->isProcessByMessageQueue());

                $apiContext->setOperationId($operationId);
                $apiContext->setResult($apiResult);
            });

        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->with(self::identicalTo($this->stepExecution))
            ->willReturn($context);
        $context->expects(self::once())
            ->method('incrementAddCount')
            ->with(0);
        $context->expects(self::once())
            ->method('incrementUpdateCount')
            ->with(0);
        $context->expects(self::once())
            ->method('incrementErrorEntriesCount')
            ->with(3);

        $this->errorManager->expects(self::once())
            ->method('readErrors')
            ->with(self::identicalTo($this->fileManager), $operationId, 0, \PHP_INT_MAX)
            ->willReturn([
                BatchError::createValidationError('some error', 'Some Error 1')->setItemIndex(0),
                BatchError::createValidationError('some error', 'Some Error 2')->setItemIndex(0)
            ]);
        $this->errorConverter->expects(self::exactly(3))
            ->method('convertToImportError')
            ->willReturnCallback(function (BatchError $error) {
                return 'Import: ' . $error->getDetail() . ' | Pointer: ' . $error->getSource()?->getPointer();
            });
        $context->expects(self::exactly(3))
            ->method('addError')
            ->withConsecutive(
                ['Import: item 1 - error 1 | Pointer: /data/0/attributes/name'],
                ['Import: Some Error 1 | Pointer: '],
                ['Import: Some Error 2 | Pointer: ']
            );

        $this->producer->expects(self::once())
            ->method('send')
            ->with(DeleteAsyncOperationTopic::getName(), ['operationId' => $operationId]);

        $this->writer->write($items);
    }
}
