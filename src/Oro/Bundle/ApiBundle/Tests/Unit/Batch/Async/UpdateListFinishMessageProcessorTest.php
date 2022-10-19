<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async;

use Oro\Bundle\ApiBundle\Batch\Async\AsyncOperationManager;
use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListFinishTopic;
use Oro\Bundle\ApiBundle\Batch\Async\UpdateListFinishMessageProcessor;
use Oro\Bundle\ApiBundle\Batch\Async\UpdateListProcessingHelper;
use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Batch\IncludeMapManager;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class UpdateListFinishMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var UpdateListProcessingHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $processingHelper;

    /** @var AsyncOperationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $operationManager;

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var IncludeMapManager|\PHPUnit\Framework\MockObject\MockObject */
    private $includeMapManager;

    /** @var UpdateListFinishMessageProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processingHelper = $this->createMock(UpdateListProcessingHelper::class);
        $this->operationManager = $this->createMock(AsyncOperationManager::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->includeMapManager = $this->createMock(IncludeMapManager::class);

        $this->processor = new UpdateListFinishMessageProcessor(
            $this->processingHelper,
            $this->operationManager,
            $this->fileManager,
            $this->includeMapManager,
            new FileNameProvider(),
        );
    }

    private function getMessage(array $body, string $messageId = ''): MessageInterface
    {
        $message = new Message();
        $message->setBody($body);
        $message->setMessageId($messageId);

        return $message;
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

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [UpdateListFinishTopic::getName()],
            UpdateListFinishMessageProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWhenNoUnlinkedIncludedData(): void
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

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWhenHasUnlinkedIncludedData(): void
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

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
