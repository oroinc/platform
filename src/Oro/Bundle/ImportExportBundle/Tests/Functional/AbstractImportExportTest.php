<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
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
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class AbstractImportExportTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    /**
     * @param ImportExportConfigurationInterface $configuration
     * @param string $expectedCsvFilePath
     * @param array $skippedColumns
     */
    protected function assertExportTemplateWorks(
        ImportExportConfigurationInterface $configuration,
        string $expectedCsvFilePath,
        array $skippedColumns = []
    ) {
        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_export_template', [
                'processorAlias' => $configuration->getExportProcessorAlias(),
                'exportTemplateJob' => $configuration->getExportTemplateJobName(),
            ])
        );

        $this->client->followRedirect();

        $this->assertExportFileData(
            $expectedCsvFilePath,
            $this->client->getRequest()->attributes->get('fileName'),
            $skippedColumns
        );
    }

    /**
     * @param ImportExportConfigurationInterface $configuration
     * @param string                             $expectedCsvFilePath
     * @param array                              $skippedColumns
     */
    protected function assertExportWorks(
        ImportExportConfigurationInterface $configuration,
        string $expectedCsvFilePath,
        array $skippedColumns = []
    ) {
        $this->assertPreExportActionExecuted($configuration);

        $preExportMessageData = $this->getOneSentMessageWithTopic(Topics::PRE_EXPORT);
        $this->clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.pre_export',
            $preExportMessageData
        );

        static::assertMessageSent(Topics::EXPORT);

        $exportMessageData = $this->getOneSentMessageWithTopic(Topics::EXPORT);
        $jobId = $exportMessageData['jobId'];
        $this->clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.export',
            $exportMessageData
        );

        $job = $this->findJob($jobId);
        $exportedFilename = $job->getData()['file'];

        $this->assertExportFileData($expectedCsvFilePath, $exportedFilename, $skippedColumns);
    }

    /**
     * @param ImportExportConfigurationInterface $configuration
     * @param string                             $importFilePath
     */
    protected function assertImportWorks(
        ImportExportConfigurationInterface $configuration,
        string $importFilePath
    ) {
        $this->assertPreImportActionExecuted($configuration, $importFilePath);

        $preImportMessageData = $this->getOneSentMessageWithTopic(Topics::PRE_HTTP_IMPORT);
        $this->clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.pre_http_import',
            $preImportMessageData
        );

        static::assertMessageSent(Topics::HTTP_IMPORT);

        $importMessageData = $this->getOneSentMessageWithTopic(Topics::HTTP_IMPORT);
        $this->clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.http_import',
            $importMessageData
        );

        $this->deleteTmpFile($preImportMessageData['fileName']);
        $this->deleteTmpFile($importMessageData['fileName']);
    }

    /**
     * @param ImportExportConfigurationInterface $configuration
     * @param string                             $importCsvFilePath
     * @param string                             $errorsFilePath
     */
    public function assertImportValidateWorks(
        ImportExportConfigurationInterface $configuration,
        string $importCsvFilePath,
        string $errorsFilePath = ''
    ) {
        $this->markTestSkipped('Unskip after BB-12769');

        $this->assertPreImportValidationActionExecuted($configuration, $importCsvFilePath);

        $preImportValidateMessageData = $this->getOneSentMessageWithTopic(Topics::PRE_HTTP_IMPORT);
        $this->clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.pre_http_import',
            $preImportValidateMessageData
        );

        $importValidateMessageData = $this->getOneSentMessageWithTopic(Topics::HTTP_IMPORT);
        $jobId = $importValidateMessageData['jobId'];
        $this->clearMessageCollector();

        static::getContainer()
            ->get('oro_importexport.async.http_import')
            ->process(
                $this->createNullMessage($importValidateMessageData),
                $this->createSessionInterfaceMock()
            );

        $job = $this->findJob($jobId);
        $jobData = $job->getData();

        if ($errorsFilePath === '') {
            static::assertFalse(array_key_exists('errorLogFile', $jobData));

            return;
        }

        static::assertSame(
            json_decode($this->getFileContent($errorsFilePath)),
            json_decode($this->getImportExportFileContent($jobData['errorLogFile']))
        );

        $this->deleteTmpFile($preImportValidateMessageData['fileName']);
        $this->deleteTmpFile($importValidateMessageData['fileName']);
        $this->deleteImportExportFile($jobData['errorLogFile']);
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    protected function getFileContent(string $filePath): string
    {
        return file_get_contents($filePath);
    }

    /**
     * @return SessionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createSessionInterfaceMock()
    {
        return $this->getMockBuilder(SessionInterface::class)->getMock();
    }

    /**
     * @param array $messageData
     *
     * @return NullMessage
     */
    protected function createNullMessage(array $messageData): NullMessage
    {
        $message = new NullMessage();

        $message->setMessageId('abc');
        $message->setBody(json_encode($messageData));

        return $message;
    }

    /**
     * @param string $topic
     *
     * @return array
     */
    protected function getOneSentMessageWithTopic(string $topic): array
    {
        $sentMessages = static::getSentMessages();

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

    /**
     * @param ImportExportConfigurationInterface $configuration
     */
    protected function assertPreExportActionExecuted(ImportExportConfigurationInterface $configuration)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_export_instant', [
                'processorAlias' => $configuration->getExportProcessorAlias(),
                'exportJob' => $configuration->getExportJobName(),
                'filePrefix' => $configuration->getFilePrefix(),
                'options' => $configuration->getRouteOptions(),
            ])
        );

        $response = static::getJsonResponseContent($this->client->getResponse(), 200);
        static::assertTrue($response['success']);

        static::assertMessageSent(Topics::PRE_EXPORT, [
            'jobName' => $configuration->getExportJobName() ?: JobExecutor::JOB_EXPORT_TO_CSV,
            'processorAlias' => $configuration->getExportProcessorAlias(),
            'outputFilePrefix' => $configuration->getFilePrefix(),
            'options' => $configuration->getRouteOptions(),
            'userId' => $this->getCurrentUser()->getId(),
            'organizationId' => $this->getSecurityToken()->getOrganizationContext()->getId()
        ]);
    }

    /**
     * @param ImportExportConfigurationInterface $configuration
     * @param string                             $importCsvFilePath
     */
    protected function assertPreImportActionExecuted(
        ImportExportConfigurationInterface $configuration,
        string $importCsvFilePath
    ) {
        $file = new UploadedFile($importCsvFilePath, basename($importCsvFilePath));
        $fileName = static::getContainer()
            ->get('oro_importexport.file.file_manager')
            ->saveImportingFile($file);

        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_import_process', [
                'processorAlias' => $configuration->getImportProcessorAlias(),
                'importJob' => $configuration->getImportJobName(),
                'fileName' => $fileName,
                'originFileName' => $file->getClientOriginalName(),
                'options' => $configuration->getRouteOptions(),
            ])
        );

        $response = static::getJsonResponseContent($this->client->getResponse(), 200);
        static::assertTrue($response['success']);

        static::assertMessageSent(Topics::PRE_HTTP_IMPORT, [
            'fileName' => $fileName,
            'process' => ProcessorRegistry::TYPE_IMPORT,
            'userId' => $this->getCurrentUser()->getId(),
            'originFileName' => $file->getClientOriginalName(),
            'jobName' => $configuration->getImportJobName() ?: JobExecutor::JOB_IMPORT_FROM_CSV,
            'processorAlias' => $configuration->getImportProcessorAlias(),
            'options' => $configuration->getRouteOptions(),
        ]);
    }

    /**
     * @param ImportExportConfigurationInterface $configuration
     * @param string                             $importCsvFilePath
     */
    protected function assertPreImportValidationActionExecuted(
        ImportExportConfigurationInterface $configuration,
        string $importCsvFilePath
    ) {
        $file = new UploadedFile($importCsvFilePath, basename($importCsvFilePath));
        $fileName = static::getContainer()
            ->get('oro_importexport.file.file_manager')
            ->saveImportingFile($file);

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

        $response = static::getJsonResponseContent($this->client->getResponse(), 200);
        static::assertTrue($response['success']);

        static::assertMessageSent(Topics::PRE_HTTP_IMPORT, [
            'fileName' => $fileName,
            'process' => ProcessorRegistry::TYPE_IMPORT_VALIDATION,
            'userId' => $this->getCurrentUser()->getId(),
            'originFileName' => $file->getClientOriginalName(),
            'jobName' => $configuration->getImportValidationJobName() ?: JobExecutor::JOB_IMPORT_VALIDATION_FROM_CSV,
            'processorAlias' => $configuration->getImportProcessorAlias(),
            'options' => $configuration->getRouteOptions(),
        ]);
    }

    /**
     * @param string $processorServiceName
     * @param array  $messageData
     */
    protected function assertMessageProcessorExecuted(string $processorServiceName, array $messageData)
    {
        $processorResult = static::getContainer()
            ->get($processorServiceName)
            ->process(
                $this->createNullMessage($messageData),
                $this->createSessionInterfaceMock()
            );

        static::assertEquals(MessageProcessorInterface::ACK, $processorResult);
    }

    /**
     * @return UsernamePasswordOrganizationToken
     */
    protected function getSecurityToken(): UsernamePasswordOrganizationToken
    {
        return static::getContainer()->get('security.token_storage')->getToken();
    }

    /**
     * @return User
     */
    protected function getCurrentUser(): User
    {
        return $this->getSecurityToken()->getUser();
    }

    /**
     * @return string|null
     */
    protected function getSerializedSecurityToken()
    {
        return static::getContainer()
            ->get('oro_security.token_serializer')
            ->serialize($this->getSecurityToken());
    }

    /**
     * @param int $id
     *
     * @return Job
     */
    protected function findJob(int $id): Job
    {
        return static::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Job::class)
            ->getRepository(Job::class)
            ->find($id);
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    protected function getImportExportFileContent(string $filename): string
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_export_download', ['fileName' => $filename])
        );

        return $this->client->getResponse()->getContent();
    }

    /**
     * @param array $data
     * @param array $ignoredColumns
     */
    protected function removedIgnoredColumnsFromData(array &$data, array $ignoredColumns)
    {
        array_walk($data, function (array $item, $key) use ($ignoredColumns, &$data) {
            $data[$key] = array_diff_key($data[$key], array_flip($ignoredColumns));
        });
    }

    /**
     * @param string $content
     *
     * @return array
     */
    protected function getParsedDataFromCSVContent(string $content)
    {
        $resultData = str_getcsv($content, "\n");
        $header = str_getcsv(array_shift($resultData));
        array_walk($resultData, function (&$row) use ($header, $resultData) {
            $row = array_combine($header, str_getcsv($row));
            $resultData[] = $row;
        });

        return $resultData;
    }

    /**
     * @param string $filename
     *
     * @return array
     */
    protected function getParsedDataFromCSVFile(string $filename)
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

    /**
     * @param string $filename
     */
    protected function deleteImportExportFile(string $filename)
    {
        static::getContainer()->get('oro_importexport.file.file_manager')->deleteFile($filename);
    }

    /**
     * @param string $filename
     */
    protected function deleteTmpFile(string $filename)
    {
        unlink(FileManager::generateTmpFilePath($filename));
    }

    /**
     * @param string $expectedCsvFilePath
     * @param string $exportedFilename
     * @param array $skippedColumns
     */
    protected function assertExportFileData($expectedCsvFilePath, $exportedFilename, array $skippedColumns)
    {
        $exportFileContent = $this->getImportExportFileContent($exportedFilename);
        $this->deleteImportExportFile($exportedFilename);

        if (empty($skippedColumns)) {
            static::assertContains(
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

            static::assertEquals(
                $expectedData,
                $exportedData
            );
        }
    }
}
