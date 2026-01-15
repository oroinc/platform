<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional;

use Oro\Bundle\ImportExportBundle\Async\Topic\ExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PostExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\SaveImportExportResultTopic;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Topic\RootJobStoppedTopic;
use Oro\Component\MessageQueue\Transport\Message as TransportMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Abstract test case for CLI imports using MQ
 */
abstract class AbstractImportExportTestCase extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    protected function assertExportTemplateWorks(
        ImportExportConfigurationInterface $configuration,
        string $expectedCsvFilePath,
        array $skippedColumns = []
    ): void {
        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_export_template', [
                'processorAlias' => $configuration->getExportTemplateProcessorAlias() ?:
                    $configuration->getExportProcessorAlias(),
                'exportTemplateJob' => $configuration->getExportTemplateJobName(),
            ])
        );

        // Take the name of the file from the header because there is no alternative way to know the filename
        $contentDisposition = $this->client->getResponse()->headers->get('Content-Disposition');
        preg_match('/^.*"?(import_template_[a-z0-9_]+.csv)"?$/', $contentDisposition, $matches);

        ob_start();
        $this->client->getResponse()->sendContent();
        $actualExportContent = ob_get_clean();

        $exportFileContent = $this->getFileContent($expectedCsvFilePath);

        $expectedData = $this->getParsedDataFromCSVContent($actualExportContent);
        $exportedData = $this->getParsedDataFromCSVContent($exportFileContent);

        if ($skippedColumns) {
            $this->removedIgnoredColumnsFromData(
                $expectedData,
                $skippedColumns
            );
        }
        self::assertEquals($expectedData, $exportedData);
        $this->deleteImportExportFile($matches[1]);
    }

    protected function assertExportWorks(
        ImportExportConfigurationInterface $configuration,
        string $expectedCsvFilePath,
        array $skippedColumns = []
    ): void {
        $messageBodyResolver = self::getContainer()->get('oro_message_queue.client.message_body_resolver');
        $this->assertPreExportActionExecuted($configuration);

        $preExportMessageData = $this->getOneSentMessageWithTopic(PreExportTopic::getName());
        self::clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.pre_export',
            $messageBodyResolver->resolveBody(PreExportTopic::getName(), $preExportMessageData)
        );

        self::assertMessageSent(ExportTopic::getName());

        $exportMessageData = $this->getOneSentMessageWithTopic(ExportTopic::getName());
        $jobId = $exportMessageData['jobId'];
        self::clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.export',
            $messageBodyResolver->resolveBody(ExportTopic::getName(), $exportMessageData)
        );

        $rootJobStoppedData = $this->getOneSentMessageWithTopic(RootJobStoppedTopic::getName());
        $this->assertMessageProcessorExecuted(
            'oro_message_queue.job.dependent_job_processor',
            $rootJobStoppedData
        );

        $postExportMessageData = $this->getOneSentMessageWithTopic(PostExportTopic::getName());
        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.post_export',
            $messageBodyResolver->resolveBody(PostExportTopic::getName(), array_merge($postExportMessageData))
        );

        $job = $this->findJob($jobId);
        $job = $job->isRoot() ? $job : $job->getRootJob();

        $saveResultMessageData = $this->getOneSentMessageWithTopic(SaveImportExportResultTopic::getName());
        self::clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.save_import_export_result_processor',
            $messageBodyResolver->resolveBody(
                SaveImportExportResultTopic::getName(),
                array_merge(
                    $saveResultMessageData,
                    [
                        'jobId' => $job->getId(),
                        'type' => ProcessorRegistry::TYPE_EXPORT,
                        'entity' => ImportExportResult::class
                    ]
                )
            )
        );

        $exportedFilename = $job->getData()['file'];
        $this->assertExportFileData($job->getId(), $expectedCsvFilePath, $exportedFilename, $skippedColumns);
    }

    protected function assertImportWorks(
        ImportExportConfigurationInterface $configuration,
        string $importFilePath
    ): void {
        $this->assertPreImportActionExecuted($configuration, $importFilePath);

        $preImportMessageData = $this->getOneSentMessageWithTopic(PreImportTopic::getName());
        self::clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.pre_import',
            $preImportMessageData
        );

        self::assertMessageSent(ImportTopic::getName());

        $importMessageData = $this->getOneSentMessageWithTopic(ImportTopic::getName());
        self::clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.import',
            $importMessageData
        );

        $this->assertTmpFilesRemoved();
    }

    protected function assertImportValidateWorks(
        ImportExportConfigurationInterface $configuration,
        string $importCsvFilePath,
        string $errorsFilePath = ''
    ): void {
        $this->markTestSkipped('Unskip after BB-12769');

        $this->assertPreImportValidationActionExecuted($configuration, $importCsvFilePath);

        self::clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.pre_import',
            $preImportValidateMessageData
        );

        $importValidateMessageData = $this->getOneSentMessageWithTopic(ImportTopic::getName());
        $jobId = $importValidateMessageData['jobId'];
        self::clearMessageCollector();

        $this->assertMessageProcessorExecuted('oro_importexport.async.import', $importValidateMessageData);

        $job = $this->findJob($jobId);
        $jobData = $job->getData();

        if ($errorsFilePath === '') {
            self::assertFalse(array_key_exists('errorLogFile', $jobData));

            return;
        }

        self::assertSame(
            json_decode($this->getFileContent($errorsFilePath), false, 512, JSON_THROW_ON_ERROR),
            json_decode($this->getImportExportFileContent($jobId), false, 512, JSON_THROW_ON_ERROR)
        );

        $this->assertTmpFilesRemoved();
        $this->deleteImportExportFile($jobData['errorLogFile']);
    }

    protected function getFileContent(string $filePath): string
    {
        return file_get_contents($filePath);
    }

    protected function createTransportMessage(array $messageData): TransportMessage
    {
        $message = new TransportMessage();

        $message->setMessageId('abc');
        $message->setBody($messageData);

        return $message;
    }

    protected function getOneSentMessageWithTopic(string $topic): array
    {
        $sentMessages = self::getSentMessages();
        foreach ($sentMessages as $messageData) {
            if ($messageData['topic'] === $topic) {
                $message = $messageData['message'];
                if ($message instanceof Message) {
                    return $message->getBody();
                }

                return $message;
            }
        }

        return [];
    }

    protected function assertPreExportActionExecuted(ImportExportConfigurationInterface $configuration): void
    {
        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_importexport_export_instant', [
                'processorAlias' => $configuration->getExportProcessorAlias(),
                'exportJob' => $configuration->getExportJobName(),
                'filePrefix' => $configuration->getFilePrefix(),
                'options' => $configuration->getRouteOptions(),
            ])
        );

        $response = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertTrue($response['success']);

        self::assertMessageSent(PreExportTopic::getName(), [
            'jobName' => $configuration->getExportJobName() ?: JobExecutor::JOB_EXPORT_TO_CSV,
            'processorAlias' => $configuration->getExportProcessorAlias(),
            'outputFilePrefix' => $configuration->getFilePrefix(),
            'options' => $configuration->getRouteOptions(),
            'userId' => $this->getCurrentUser()->getId(),
            'organizationId' => $this->getSecurityToken()->getOrganization()->getId()
        ]);
    }

    protected function assertPreImportActionExecuted(
        ImportExportConfigurationInterface $configuration,
        string $importCsvFilePath
    ): void {
        $file = new UploadedFile($importCsvFilePath, basename($importCsvFilePath));
        $fileName = $this->getFileManager()->saveImportingFile($file);

        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_importexport_import_process', [
                'processorAlias' => $configuration->getImportProcessorAlias(),
                'importJob' => $configuration->getImportJobName(),
                'fileName' => $fileName,
                'originFileName' => $file->getClientOriginalName(),
                'options' => $configuration->getRouteOptions(),
            ])
        );

        $response = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertTrue($response['success']);

        self::assertMessageSent(
            PreImportTopic::getName(),
            [
                'fileName' => $fileName,
                'process' => ProcessorRegistry::TYPE_IMPORT,
                'userId' => $this->getCurrentUser()->getId(),
                'originFileName' => $file->getClientOriginalName(),
                'jobName' => $configuration->getImportJobName() ?: JobExecutor::JOB_IMPORT_FROM_CSV,
                'processorAlias' => $configuration->getImportProcessorAlias(),
                'options' => $configuration->getRouteOptions(),
            ]
        );
    }

    protected function assertPreImportValidationActionExecuted(
        ImportExportConfigurationInterface $configuration,
        string $importCsvFilePath
    ): void {
        $file = new UploadedFile($importCsvFilePath, basename($importCsvFilePath));
        $fileName = $this->getFileManager()->saveImportingFile($file);

        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_import_validate', [
                'processorAlias' => $configuration->getImportProcessorAlias(),
                'importValidateJob' => $configuration->getImportValidationJobName(),
                'fileName' => $fileName,
                'originFileName' => $file->getClientOriginalName(),
                'options' => $configuration->getRouteOptions(),
            ])
        );

        $response = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertTrue($response['success']);

        self::assertMessageSent(PreImportTopic::getName(), [
            'fileName' => $fileName,
            'process' => ProcessorRegistry::TYPE_IMPORT_VALIDATION,
            'userId' => $this->getCurrentUser()->getId(),
            'originFileName' => $file->getClientOriginalName(),
            'jobName' => $configuration->getImportValidationJobName() ?: JobExecutor::JOB_IMPORT_VALIDATION_FROM_CSV,
            'processorAlias' => $configuration->getImportProcessorAlias(),
            'options' => $configuration->getRouteOptions(),
        ]);
    }

    protected function assertMessageProcessorExecuted(string $processorServiceName, array $messageData): void
    {
        $processorResult = self::getContainer()
            ->get($processorServiceName)
            ->process(
                $this->createTransportMessage($messageData),
                $this->createMock(SessionInterface::class)
            );

        self::assertEquals(MessageProcessorInterface::ACK, $processorResult);

        self::flushMessagesBuffer();
    }

    protected function assertExportFileData(
        int $jobId,
        string $expectedCsvFilePath,
        string $exportedFilename,
        array $skippedColumns
    ): void {
        $exportFileContent = $this->getImportExportFileContent($jobId);
        $this->deleteImportExportFile($exportedFilename);

        if (empty($skippedColumns)) {
            self::assertStringContainsString(
                $this->getFileContent($expectedCsvFilePath),
                $exportFileContent
            );
        } else {
            $expectedData = $this->getParsedDataFromCSVFile($expectedCsvFilePath);
            $exportedData = $this->getParsedDataFromCSVContent($exportFileContent);
            $this->removedIgnoredColumnsFromData(
                $exportedData,
                $skippedColumns
            );

            self::assertEquals(
                $expectedData,
                $exportedData
            );
        }
    }

    protected function getSecurityToken(): UsernamePasswordOrganizationToken
    {
        return self::getContainer()->get('security.token_storage')->getToken();
    }

    protected function getCurrentUser(): User
    {
        return $this->getSecurityToken()->getUser();
    }

    protected function getSerializedSecurityToken(): ?string
    {
        return self::getContainer()
            ->get('oro_security.token_serializer')
            ->serialize($this->getSecurityToken());
    }

    protected function findJob(int $id): Job
    {
        return self::getContainer()->get('doctrine')->getRepository(Job::class)
            ->find($id);
    }

    protected function getImportExportFileContent(int $jobId): string
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_export_download', ['jobId' => $jobId])
        );

        ob_start();
        $this->client->getResponse()->sendContent();

        return ob_get_clean();
    }

    protected function removedIgnoredColumnsFromData(array &$data, array $ignoredColumns)
    {
        array_walk($data, function (array $item, $key) use ($ignoredColumns, &$data) {
            $data[$key] = array_diff_key($data[$key], array_flip($ignoredColumns));
        });
    }

    protected function getParsedDataFromCSVContent(string $content): array
    {
        $resultData = str_getcsv($content, "\n");
        $header = str_getcsv(array_shift($resultData));
        array_walk($resultData, function (&$row) use ($header, $resultData) {
            $row = array_combine($header, str_getcsv($row));
            $resultData[] = $row;
        });

        return $resultData;
    }

    protected function getParsedDataFromCSVFile(string $filename): array
    {
        $resultData = [];
        $handler = fopen($filename, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $resultData[] = $row;
        }

        fclose($handler);

        return $resultData;
    }

    protected function deleteImportExportFile(string $filename)
    {
        $this->getFileManager()->deleteFile($filename);
    }

    protected function assertTmpFilesRemoved(): void
    {
        $tempFileHandles = ReflectionUtil::getPropertyValue($this->getFileManager(), 'tempFileHandles');
        foreach ($tempFileHandles as $tempFileHandle) {
            self::assertFileDoesNotExist(stream_get_meta_data($tempFileHandle)['uri']);
        }
    }

    private function getFileManager(): FileManager
    {
        return self::getContainer()->get('oro_importexport.file.file_manager');
    }
}
