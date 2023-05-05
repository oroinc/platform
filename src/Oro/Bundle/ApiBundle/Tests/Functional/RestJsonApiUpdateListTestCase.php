<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListProcessChunkTopic;
use Oro\Bundle\ApiBundle\Batch\ChunkSizeProvider;
use Oro\Bundle\ApiBundle\Batch\ErrorManager;
use Oro\Bundle\ApiBundle\Batch\JsonUtil;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor\BatchUpdateExceptionController;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\MessageQueue\Client\MessageBodyResolverInterface;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The base class for REST Batch API that conforms the JSON:API specification functional tests.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class RestJsonApiUpdateListTestCase extends RestJsonApiTestCase
{
    use MessageQueueExtension;

    protected function getFileManager(): FileManager
    {
        return self::getContainer()->get('oro_api.batch.file_manager');
    }

    protected function getSourceDataFileManager(): FileManager
    {
        return self::getContainer()->get('oro_api.batch.file_manager.source_data');
    }

    protected function getErrorManager(): ErrorManager
    {
        return self::getContainer()->get('oro_api.batch.error_manager');
    }

    protected function getChunkSizeProvider(): ChunkSizeProvider
    {
        return self::getContainer()->get('oro_api.batch.chunk_size_provider');
    }

    protected function getJobProcessor(): JobProcessor
    {
        return self::getContainer()->get('oro_message_queue.job.processor');
    }

    protected function getJob(int $jobId): Job
    {
        return $this->getJobProcessor()->findJobById($jobId);
    }

    protected function getMessageBodyResolver(): MessageBodyResolverInterface
    {
        return self::getContainer()->get('oro_message_queue.client.message_body_resolver');
    }

    protected function getTokenStorage(): TokenStorageInterface
    {
        return self::getContainer()->get('security.token_storage');
    }

    protected function getFileContentAndDeleteFile(string $fileName): ?string
    {
        $fileManager = $this->getFileManager();
        $dataFileContent = $fileManager->getFileContent($fileName);
        if (null !== $dataFileContent) {
            $fileManager->deleteFile($fileName);
        }

        return $dataFileContent;
    }

    protected function extractJobIdFromMessage(MessageInterface $message): int
    {
        $jobId = $message->getBody()['jobId'];
        self::assertIsInt($jobId);

        return $jobId;
    }

    protected function extractOperationIdFromContentLocationHeader(Response $response): int
    {
        self::assertTrue($response->headers->has('Content-Location'));
        $locationHeader = $response->headers->get('Content-Location');
        $delimiterPosition = strrpos($locationHeader, '/');

        return substr($locationHeader, $delimiterPosition + 1);
    }

    protected function createAsyncOperation(string $entityClass, array $data): AsyncOperation
    {
        $dataFileName = UUIDGenerator::v4();
        $dataFileContext = JsonUtil::encode($data);
        $this->getSourceDataFileManager()->writeToStorage($dataFileContext, $dataFileName);

        $operation = new AsyncOperation();
        $operation->setActionName(ApiAction::UPDATE_LIST);
        $operation->setEntityClass($entityClass);
        $operation->setDataFileName($dataFileName);
        $operation->setStatus(AsyncOperation::STATUS_NEW);

        $this->getEntityManager()->persist($operation);
        $this->getEntityManager()->flush();

        return $operation;
    }

    protected function assertAsyncOperationStatus(int $operationId, array $attributes): void
    {
        $this->getEntityManager()->clear();
        $response = $this->get(['entity' => 'asyncoperations', 'id' => (string)$operationId]);
        $this->assertAsyncOperationResponse($operationId, $attributes, $response);
    }

    protected function assertAsyncOperationResponse(int $operationId, array $attributes, Response $response): void
    {
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'asyncoperations',
                    'id'         => (string)$operationId,
                    'attributes' => $attributes
                ]
            ],
            $response
        );
    }

    protected function assertAsyncOperationError(array $expectedError, int $operationId): void
    {
        self::assertNotEmpty($expectedError, 'The expected error should not be empty.');
        $this->assertAsyncOperationErrors([$expectedError], $operationId);
    }

    protected function assertAsyncOperationErrors(array $expectedErrors, int $operationId): void
    {
        if (!$expectedErrors && !$this->getErrorManager()->readErrors($this->getFileManager(), $operationId, 0, 1)) {
            return;
        }

        $response = $this->getAsyncOperationErrors($operationId);
        $this->assertResponseContains($this->buildAsyncOperationErrors($expectedErrors), $response);
        $responseData = self::jsonToArray($response->getContent());
        $errors = $responseData[JsonApiDoc::DATA];
        if (count($expectedErrors) !== count($errors)) {
            self::fail(sprintf('Expected %d error(s), got %d error(s).', count($expectedErrors), count($errors)));
        }
    }

    protected function dumpYmlTemplateForAsyncOperationErrors(int $operationId): void
    {
        $this->dumpYmlTemplate(null, $this->getAsyncOperationErrors($operationId));
    }

    private function getAsyncOperationErrors(int $operationId): Response
    {
        $this->getEntityManager()->clear();

        return $this->getSubresource(
            ['entity' => 'asyncoperations', 'id' => (string)$operationId, 'association' => 'errors']
        );
    }

    private function buildAsyncOperationErrors(array $errors): array
    {
        $result = [];
        $i = 0;
        foreach ($errors as $error) {
            self::assertArrayHasKey('id', $error, sprintf('Error index: %d.', $i));
            $errorId = $error['id'];
            unset($error['id']);
            $result[] = ['type' => 'asyncoperationerrors', 'id' => $errorId, 'attributes' => $error];
            $i++;
        }

        return ['data' => $result];
    }

    protected function assertAsyncOperationRootJobStatus(
        int $operationId,
        string $status,
        float $progress,
        bool $hasErrors = false,
        array $summary = []
    ): void {
        $this->getEntityManager()->clear();
        $operation = $this->getEntityManager()->find(AsyncOperation::class, $operationId);
        $rootJob = $this->getJob($operation->getJobId());
        self::assertEquals($status, $rootJob->getStatus(), 'Root job status');
        self::assertEquals($progress, $rootJob->getJobProgress(), 'Root job progress');
        self::assertArrayContains(['api_operation_id' => $operationId], $rootJob->getData(), 'Root job data');
        $operationStatus = null;
        if (Job::STATUS_SUCCESS === $status) {
            $operationStatus = AsyncOperation::STATUS_SUCCESS;
        } elseif (Job::STATUS_FAILED === $status) {
            $operationStatus = AsyncOperation::STATUS_FAILED;
        } elseif (Job::STATUS_RUNNING === $status) {
            $operationStatus = AsyncOperation::STATUS_RUNNING;
        }
        if (null !== $operationStatus) {
            self::assertEquals($operationStatus, $operation->getStatus(), 'Operation status');
        }
        self::assertArrayContains($summary, $operation->getSummary(), 'Operation summary');
        if (isset($summary['errorCount'])) {
            $content = self::jsonToArray($this->getAsyncOperationErrors($operationId)->getContent());
            self::assertCount(
                $summary['errorCount'],
                $content[JsonApiDoc::DATA],
                'The number of errors should be the same as in the "errorCount" attribute of the summary'
            );
        }
        self::assertSame($hasErrors, $operation->isHasErrors(), 'Operation hasErrors');
    }

    protected function sendUpdateListRequest(string $entityClass, array|string $data): int
    {
        $response = $this->cpatch(['entity' => $this->getEntityType($entityClass)], $data);
        return $this->extractOperationIdFromContentLocationHeader($response);
    }

    protected function processUpdateListMessage(Context $messageContext): void
    {
        self::assertEquals(MessageProcessorInterface::ACK, $messageContext->getStatus());
    }

    protected function processUpdateList(string $entityClass, array|string $data, bool $assertNoErrors = true): int
    {
        $operationId = $this->sendUpdateListRequest($entityClass, $data);

        $tokenStorage = $this->getTokenStorage();
        $token = $this->getTokenStorage()->getToken();

        $this->consumeMessages();

        //refresh token after resetting in consumer
        $tokenStorage->setToken($token);
        $this->consumeMessages();

        $this->consumeMessages();

        $this->consumeMessages();

        $tokenStorage = $this->getTokenStorage();
        $token = $this->getTokenStorage()->getToken();

        $this->consumeMessages();

        //refresh token after resetting in consumer
        $tokenStorage->setToken($token);
        $this->consumeMessages();

        $this->consumeMessages();

        $this->consumeMessages();
        if ($assertNoErrors) {
            $this->assertAsyncOperationErrors([], $operationId);
        }

        return $operationId;
    }

    private function getBatchUpdateExceptionController(): BatchUpdateExceptionController
    {
        return self::getContainer()->get('oro_api.tests.batch_update_exception_controller');
    }

    protected function processUpdateListAndValidateJobs(
        string       $entityClass,
        array|string $data,
        array        $expectedJobs,
        ?string      $failedGroupName = null,
        ?array       $entityConfig = null
    ): int {
        $operationId = $this->sendUpdateListRequest($entityClass, $data);

        $tokenStorage = $this->getTokenStorage();
        $token = $this->getTokenStorage()->getToken();

        $this->consumeMessages();

        if ($failedGroupName) {
            $this->getBatchUpdateExceptionController()->setFailedGroups([$failedGroupName]);
        }

        if ($entityConfig) {
            $this->appendEntityConfig($entityClass, $entityConfig);
        }

        //refresh token after resetting in consumer
        $tokenStorage->setToken($token);

        $processedUpdateListChunkMessages = $this->consumeAllMessages(
            UpdateListProcessChunkTopic::getName()
        );

        self::assertCount(
            count($expectedJobs),
            $processedUpdateListChunkMessages,
            UpdateListProcessChunkTopic::getName()
        );

        $messages = [];
        $i = 0;
        foreach ($processedUpdateListChunkMessages as $processedUpdateListChunkMessage) {
            $comment = sprintf('Job index: %d.', $i);
            $expectedResult = $expectedJobs[$i]['result'] ?? MessageProcessorInterface::ACK;
            self::assertEquals($expectedResult, $processedUpdateListChunkMessage['context']->getStatus(), $comment);
            $messages[] = $processedUpdateListChunkMessage['message'];
            $i++;
        }

        $i = 0;
        foreach ($messages as $message) {
            $comment = sprintf('Job index: %d.', $i);
            $job = $this->getJob($this->extractJobIdFromMessage($message));
            $expectedJobData = $expectedJobs[$i];
            $expectedStatus = $expectedJobData['status'] ?? Job::STATUS_SUCCESS;
            unset($expectedJobData['result'], $expectedJobData['status']);
            self::assertEquals($expectedStatus, $job->getStatus(), $comment);
            self::assertArrayContains($expectedJobData, $job->getData(), $comment);
            $i++;
        }

        return $operationId;
    }

    protected function processUpdateListDelayedCreationOfChunkJobs(
        string $entityClass,
        array|string $data,
        bool $assertNoErrors = true
    ): int {
        $response = $this->cpatch(['entity' => $this->getEntityType($entityClass)], $data);
        $operationId = $this->extractOperationIdFromContentLocationHeader($response);

        $this->consumeAllMessages();

        if ($assertNoErrors) {
            $this->assertAsyncOperationErrors([], $operationId);
        }

        return $operationId;
    }
}
