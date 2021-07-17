<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async;

use Oro\Bundle\ApiBundle\Batch\Async\AsyncOperationManager;
use Oro\Bundle\ApiBundle\Batch\Async\Topics;
use Oro\Bundle\ApiBundle\Batch\Async\UpdateListFinishMessageProcessor;
use Oro\Bundle\ApiBundle\Batch\Async\UpdateListProcessingHelper;
use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Batch\IncludeMapManager;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class UpdateListFinishMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|UpdateListProcessingHelper */
    private $processingHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AsyncOperationManager */
    private $operationManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileManager */
    private $fileManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|IncludeMapManager */
    private $includeMapManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var UpdateListFinishMessageProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processingHelper = $this->createMock(UpdateListProcessingHelper::class);
        $this->operationManager = $this->createMock(AsyncOperationManager::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->includeMapManager = $this->createMock(IncludeMapManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new UpdateListFinishMessageProcessor(
            $this->processingHelper,
            $this->operationManager,
            $this->fileManager,
            $this->includeMapManager,
            new FileNameProvider(),
            $this->logger
        );
    }

    /**
     * @param array $body
     * @param string $messageId
     *
     * @return MessageInterface
     */
    private function getMessage(array $body, string $messageId = '')
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn(JSON::encode($body));
        $message->expects(self::any())
            ->method('getMessageId')
            ->willReturn($messageId);

        return $message;
    }

    /**
     * @return SessionInterface
     */
    private function getSession()
    {
        return $this->createMock(SessionInterface::class);
    }

    private function getUnlinkedIncludedDataError(string $sectionName, int $itemIndex): BatchError
    {
        $error = BatchError::createValidationError(
            Constraint::REQUEST_DATA,
            'The entity should have a relationship with at least one primary entity'
            . ' and this should be explicitly specified in the request'
        );
        $error->setSource(ErrorSource::createByPointer(sprintf('/%s/%s', $sectionName, $itemIndex)));

        return $error;
    }

    public function testGetSubscribedTopics()
    {
        self::assertEquals(
            [Topics::UPDATE_LIST_FINISH],
            UpdateListFinishMessageProcessor::getSubscribedTopics()
        );
    }

    public function testShouldRejectInvalidMessage()
    {
        $message = $this->getMessage(['key' => 'value']);

        $this->logger->expects(self::once())
            ->method('critical')
            ->with('Got invalid message.');

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessWhenNoUnlinkedIncludedData()
    {
        $operationId = 123;
        $dataFileName = 'testFile';
        $aggregateTime = 100;
        $message = $this->getMessage([
            'operationId' => $operationId,
            'entityClass' => 'Test\Entity',
            'requestType' => ['testRequest'],
            'version'     => '1.1',
            'fileName'    => $dataFileName
        ]);

        $this->includeMapManager->expects(self::once())
            ->method('getNotLinkedIncludedItemIndexes')
            ->with(self::identicalTo($this->fileManager), $operationId)
            ->willReturn([]);
        $this->operationManager->expects(self::never())
            ->method('addErrors');
        $this->processingHelper->expects(self::exactly(4))
            ->method('safeDeleteFile')
            ->withConsecutive(
                [sprintf('api_%s_info', $operationId)],
                [sprintf('api_%s_include_index', $operationId)],
                [sprintf('api_%s_include_index_processed', $operationId)],
                [sprintf('api_%s_include_index_linked', $operationId)]
            );
        $this->processingHelper->expects(self::once())
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($aggregateTime);
        $this->operationManager->expects(self::once())
            ->method('incrementAggregateTime')
            ->with($operationId, $aggregateTime);
        $this->logger->expects(self::never())
            ->method(self::anything());

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWhenHasUnlinkedIncludedData()
    {
        $operationId = 123;
        $dataFileName = 'testFile';
        $aggregateTime = 100;
        $message = $this->getMessage([
            'operationId' => $operationId,
            'entityClass' => 'Test\Entity',
            'requestType' => ['testRequest'],
            'version'     => '1.1',
            'fileName'    => $dataFileName
        ]);

        $this->includeMapManager->expects(self::once())
            ->method('getNotLinkedIncludedItemIndexes')
            ->with(self::identicalTo($this->fileManager), $operationId)
            ->willReturn(['included' => [3, 4]]);
        $this->operationManager->expects(self::once())
            ->method('addErrors')
            ->with(
                $operationId,
                $dataFileName,
                [
                    $this->getUnlinkedIncludedDataError('included', 3),
                    $this->getUnlinkedIncludedDataError('included', 4)
                ]
            );
        $this->processingHelper->expects(self::exactly(4))
            ->method('safeDeleteFile')
            ->withConsecutive(
                [sprintf('api_%s_info', $operationId)],
                [sprintf('api_%s_include_index', $operationId)],
                [sprintf('api_%s_include_index_processed', $operationId)],
                [sprintf('api_%s_include_index_linked', $operationId)]
            );
        $this->processingHelper->expects(self::once())
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($aggregateTime);
        $this->operationManager->expects(self::once())
            ->method('incrementAggregateTime')
            ->with($operationId, $aggregateTime);
        $this->logger->expects(self::never())
            ->method(self::anything());

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
