<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Router;

class ImportExportResultSummarizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Router|MockObject
     */
    private $router;

    /**
     * @var ConfigManager|MockObject
     */
    private $configManager;

    /**
     * @var FileManager|MockObject
     */
    private $fileManager;

    /**
     * @var ManagerRegistry|MockObject
     */
    private $registry;

    /**
     * @var ImportExportResultSummarizer
     */
    private $summirizer;

    protected function setUp(): void
    {
        $this->router = $this->createMock(Router::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->summirizer = new ImportExportResultSummarizer(
            $this->router,
            $this->configManager,
            $this->fileManager
        );
        $this->summirizer->setRegistry($this->registry);
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

        $result = $this->summirizer->getSummaryResultForNotification($job, 'import.csv');

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

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                $this->equalTo('oro_importexport_job_error_log'),
                $this->equalTo(['jobId' => $job->getId()])
            )
            ->willReturn('/log/12345');

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn('http://127.0.0.1');

        $result = $this->summirizer->getSummaryResultForNotification($job, 'import.csv');

        $this->assertEquals($expectedData, $result);
    }

    public function testShouldReturnErrorLog()
    {
        $job = new Job();
        $job->setId(1);

        $repo = $this->createMock(JobRepository::class);
        $repo->expects($this->once())
            ->method('getChildJobErrorLogFiles')
            ->with($job)
            ->willReturn([
                ['id' => 1, 'error_log_file' => 'test.json'],
                ['id' => 2, 'error_log_file' => 'test2.json']
            ]);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Job::class)
            ->willReturn($repo);

        $this->fileManager
            ->expects($this->at(0))
            ->method('isFileExist')
            ->with('test.json')
            ->willReturn(true);
        $this->fileManager
            ->expects($this->at(1))
            ->method('getContent')
            ->with('test.json')
            ->willReturn(json_encode(['Tests error in import.']));
        $this->fileManager
            ->expects($this->at(2))
            ->method('isFileExist')
            ->with('test2.json')
            ->willReturn(false);

        $summary = $this->summirizer->getErrorLog($job);

        $this->assertEquals("Tests error in import.\nLog file of job id: \"2\" was not found.\n", $summary);
    }

    public function testProcessExportData()
    {
        $jobId = 1;
        $expectedResult = [
            'exportResult' => [
                'success' => true,
                'url' => sprintf('127.0.0.1/%s', $jobId),
                'readsCount' => 10,
                'errorsCount' => 0,
                'entities' => 'TestEntity',
                'fileName' => 'export_result',
                'downloadLogUrl' => sprintf('127.0.0.1/%s.log', $jobId)
            ],
            'jobName' => 'test.job.name',
        ];

        $this->assertUrlCalls();
        $rootJob = new Job();
        $rootJob->setId($jobId);
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

        $result = $this->summirizer->processSummaryExportResultForNotification($rootJob, 'export_result');

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

        $this->assertUrlCalls();
        $rootJob = new Job();
        $rootJob->setId(1);
        $rootJob->setName('test.job.name');
        $childJob = new Job();
        $chunkJob = new Job();
        $rootJob->addChildJob($childJob);
        $rootJob->addChildJob($chunkJob);
        $childJob->setData([]);
        $chunkJob->setData([]);

        $result = $this->summirizer->processSummaryExportResultForNotification($rootJob, 'export_result');

        $this->assertEquals($expectedResult, $result);
    }

    private function assertUrlCalls(int $jobId = 1)
    {
        $this->router
            ->expects($this->at(0))
            ->method('generate')
            ->with('oro_importexport_export_download', ['jobId' => $jobId])
            ->willReturnCallback(function ($route, $args) {
                return sprintf('/%s', $args['jobId']);
            });
        $this->router
            ->expects($this->at(1))
            ->method('generate')
            ->with('oro_importexport_job_error_log', ['jobId' => $jobId])
            ->willReturnCallback(function ($route, $args) {
                return sprintf('/%s.log', $args['jobId']);
            });

        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn('127.0.0.1');
    }
}
