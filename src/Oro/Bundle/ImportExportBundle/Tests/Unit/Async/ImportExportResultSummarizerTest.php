<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Symfony\Component\Routing\Router;

class ImportExportResultSummarizerTest extends \PHPUnit\Framework\TestCase
{
    public function testCanBeConstructedWithRequiredAttributes()
    {
        new ImportExportResultSummarizer(
            $this->createRouterMock(),
            $this->createConfigManagerMock(),
            $this->createFileManagerMock()
        );
    }

    public function testShouldReturnCorrectSummaryInformationWithoutErrorInImport()
    {
        $result = [
            'success' => true,
            'errors' => [],
            'counts' => [
                'add' => 2,
                'replace' => 5,
                'process' => 7,
                'read' => 7,
            ],
        ];
        $expectedData['data'] = [
            'hasError' => false,
            'successParts' => 2,
            'totalParts' => 2,
            'errors' => 0,
            'process' => 14,
            'read' => 14,
            'add' => 4,
            'replace' => 10,
            'update' => 0,
            'delete' => 0,
            'error_entries' => 0,
            'fileName' => 'import.csv',
            'downloadLogUrl' => '',
        ];

        $job = new Job();

        $childJob1 = new Job();
        $childJob1->setData($result);
        $job->addChildJob($childJob1);

        $childJob2 = new Job();
        $childJob2->setData($result);
        $job->addChildJob($childJob2);

        $consolidateService = new ImportExportResultSummarizer(
            $this->createRouterMock(),
            $this->createConfigManagerMock(),
            $this->createFileManagerMock()
        );

        $result = $consolidateService->getSummaryResultForNotification($job, 'import.csv');

        $this->assertEquals($expectedData, $result);
    }

    public function testShouldReturnCorrectSummaryInformationWithErrorLink()
    {
        $data = [
            'success' => true,
            'errors' => [
                'error 1',
                'error 2',
            ],
            'counts' => [
                'add' => 2,
                'errors' => 2,
                'replace' => 1,
                'process' => 5,
                'read' => 5,
            ],
        ];
        $expectedData['data'] = [
            'hasError' => true,
            'successParts' => 2,
            'totalParts' => 2,
            'errors' => 4,
            'process' => 10,
            'read' => 10,
            'add' => 4,
            'replace' => 2,
            'update' => 0,
            'delete' => 0,
            'error_entries' => 0,
            'fileName' => 'import.csv',
            'downloadLogUrl' => 'http://127.0.0.1/log/12345',
        ];

        $job = new Job();
        $job->setId(12345);

        $childJob1 = new Job();
        $childJob1->setData($data);
        $job->addChildJob($childJob1);

        $childJob2 = new Job();
        $childJob2->setData($data);
        $job->addChildJob($childJob2);

        $router = $this->createRouterMock();
        $router
            ->expects($this->once())
            ->method('generate')
            ->with(
                $this->equalTo('oro_importexport_job_error_log'),
                $this->equalTo(['jobId' => $job->getId()])
            )
            ->willReturn('/log/12345')
        ;

        $configManager = $this->createConfigManagerMock();
        $configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn('http://127.0.0.1')
        ;

        $consolidateService = new ImportExportResultSummarizer(
            $router,
            $configManager,
            $this->createFileManagerMock()
        );

        $result = $consolidateService->getSummaryResultForNotification($job, 'import.csv');

        $this->assertEquals($expectedData, $result);
    }

    public function testShouldReturnErrorLog()
    {
        $data = [
            'success' => true,
            'errorLogFile' => 'test.json',
            'counts' => [
                'add' => 2,
                'errors' => 1,
                'replace' => 1,
                'process' => 4,
                'read' => 4,
            ],
        ];

        $job = new Job();
        $job->setId(1);
        $childJob1 = new Job();
        $childJob1->setData($data);
        $job->addChildJob($childJob1);
        $childJob2 = new Job();
        $childJob2->setId(2);
        $childJob2->setData(array_merge($data, ['errorLogFile' => 'test2.json']));
        $job->addChildJob($childJob2);

        $fileManager = $this->createFileManagerMock();
        $fileManager
            ->expects($this->at(0))
            ->method('isFileExist')
            ->with('test.json')
            ->willReturn(true);
        $fileManager
            ->expects($this->at(1))
            ->method('getContent')
            ->with('test.json')
            ->willReturn(json_encode(['Tests error in import.']));
        $fileManager
            ->expects($this->at(2))
            ->method('isFileExist')
            ->with('test2.json')
            ->willReturn(false);

        $consolidateService = new ImportExportResultSummarizer(
            $this->createRouterMock(),
            $this->createConfigManagerMock(),
            $fileManager
        );
        $summary = $consolidateService->getErrorLog($job);

        $this->assertEquals("Tests error in import.\nLog file of job id: \"2\" was not found.\n", $summary);
    }

    public function testProcessExportData()
    {
        $expectedResult = [
            'exportResult' => [
                'success' => true,
                'url' => '127.0.0.1/export.log',
                'readsCount' => 10,
                'errorsCount' => 0,
                'entities' => 'TestEntity',
                'fileName' => 'export_result',
                'downloadLogUrl' => '127.0.0.1/1.log'
            ],
            'jobName' => 'test.job.name',
        ];

        $consolidateService = $this->createConsolidatedService();

        $rootJob = new Job();
        $rootJob->setId(1);
        $rootJob->setName('test.job.name');
        $childJob = new Job();
        $chunkJob = new Job();
        $rootJob->addChildJob($childJob);
        $rootJob->addChildJob($chunkJob);
        $childJob->setData([
            'success' => true,
            'file' => 'test',
            'readsCount' => 10,
            'errorsCount' => 0,
            'entities' => 'TestEntity',
            'errors' => []
        ]);
        $chunkJob->setData([]);

        $result = $consolidateService->processSummaryExportResultForNotification($rootJob, 'export_result');

        $this->assertEquals($expectedResult, $result);
    }

    public function testProcessExportDataWithoutEntities()
    {
        $expectedResult = [
            'exportResult' => [
                'success' => false,
                'url' => '127.0.0.1/1.log',
                'readsCount' => 0,
                'errorsCount' => 0,
                'entities' => null,
                'fileName' => 'export_result',
                'downloadLogUrl' => '127.0.0.1/1.log'
            ],
            'jobName' => 'test.job.name',
        ];

        $consolidateService = $this->createConsolidatedService();

        $rootJob = new Job();
        $rootJob->setId(1);
        $rootJob->setName('test.job.name');
        $childJob = new Job();
        $chunkJob = new Job();
        $rootJob->addChildJob($childJob);
        $rootJob->addChildJob($chunkJob);
        $childJob->setData([]);
        $chunkJob->setData([]);

        $result = $consolidateService->processSummaryExportResultForNotification($rootJob, 'export_result');

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Router
     */
    private function createRouterMock()
    {
        return $this->createMock(Router::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ConfigManager
     */
    private function createConfigManagerMock()
    {
        return $this->createMock(ConfigManager::class);
    }

    /**
    * @return \PHPUnit\Framework\MockObject\MockObject|FileManager
    */
    private function createFileManagerMock()
    {
        return $this->createMock(FileManager::class);
    }

    /**
     * @return ImportExportResultSummarizer
     */
    private function createConsolidatedService()
    {
        $routerMock = $this->createRouterMock();
        $routerMock
            ->expects($this->at(0))
            ->method('generate')
            ->with('oro_importexport_export_download', ['fileName' => 'export_result'])
            ->willReturn('/export.log');
        $routerMock
            ->expects($this->at(1))
            ->method('generate')
            ->with('oro_importexport_job_error_log', ['jobId' => 1])
            ->willReturn('/1.log');

        $configManagerMock = $this->createConfigManagerMock();
        $configManagerMock
            ->expects($this->exactly(2))
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn('127.0.0.1');

        $consolidateService = new ImportExportResultSummarizer(
            $routerMock,
            $configManagerMock,
            $this->createFileManagerMock()
        );

        return $consolidateService;
    }
}
