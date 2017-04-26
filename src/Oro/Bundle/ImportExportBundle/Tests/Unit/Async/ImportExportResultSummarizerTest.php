<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Symfony\Component\Routing\Router;

class ImportExportResultSummarizerTest extends \PHPUnit_Framework_TestCase
{
    public function testCanBeConstructedWithRequiredAttributes()
    {
        new ImportExportResultSummarizer(
            $this->createRouterMock(),
            $this->createConfigManagerMock(),
            $this->createRenderMock(),
            $this->createManagerRegistryMock()
        );
    }

    public function testShouldReturnCorrectSummaryInformationWithoutErrorInImport()
    {
        $data = [
            'success' => true,
            'errors' => [],
            'counts' => [
                'add' => 2,
                'replace' => 5,
                'process' => 7,
                'read' => 7,
            ],
        ];
        $expectedData = [
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
        $childJob1->setData($data);
        $job->addChildJob($childJob1);
        $childJob2 = new Job();
        $childJob2->setData($data);
        $job->addChildJob($childJob2);

        $template = new EmailTemplate();
        $repository = $this->createEmailTemplateRepsitoryMock();
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => ImportExportResultSummarizer::TEMPLATE_IMPORT_RESULT])
            ->willReturn($template)
            ;
        $objectManager = $this->createObjectManagerMock();
        $objectManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($repository)
            ;

        $managerRegistry = $this->createManagerRegistryMock();
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(EmailTemplate::class)
            ->willReturn($objectManager)
        ;

        $render = $this->createRenderMock();
        $render
            ->expects($this->once())
            ->method('compileMessage')
            ->with($template, ['data' => $expectedData])
            ->willReturn(['subject', 'summary'])
            ;
        $consolidateService = new ImportExportResultSummarizer(
            $this->createRouterMock(),
            $this->createConfigManagerMock(),
            $render,
            $managerRegistry
        );

        list($subject, $summary) = $consolidateService->getSummaryResultForNotification(
            $job,
            'import.csv',
            ImportExportResultSummarizer::TEMPLATE_IMPORT_RESULT
        );

        $this->assertEquals('subject', $subject);
        $this->assertEquals('summary', $summary);
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
        $expectedData = [
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
            'downloadLogUrl' => '127.0.0.1/log/12345',
        ];

        $job = new Job();
        $childJob1 = new Job();
        $childJob1->setData($data);
        $job->setId(12345);
        $job->addChildJob($childJob1);
        $childJob2 = new Job();
        $childJob2->setData($data);
        $job->addChildJob($childJob2);

        $template = new EmailTemplate();
        $repository = $this->createEmailTemplateRepsitoryMock();
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => ImportExportResultSummarizer::TEMPLATE_IMPORT_RESULT])
            ->willReturn($template)
        ;
        $objectManager = $this->createObjectManagerMock();
        $objectManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($repository);
        $managerRegistry = $this->createManagerRegistryMock();
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(EmailTemplate::class)
            ->willReturn($objectManager);
        $render = $this->createRenderMock();
        $render
            ->expects($this->once())
            ->method('compileMessage')
            ->with($template, ['data' => $expectedData])
            ->willReturn(['subject', 'summary'])
        ;

        $router = $this->createRouterMock();
        $router
            ->expects($this->once())
            ->method('generate')
            ->with(
                'oro_importexport_import_error_log',
                ['jobId' => $job->getId()]
            )
            ->willReturn('log/12345');
        $configManager = $this->createConfigManagerMock();
        $configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn('127.0.0.1/');
        $consolidateService = new ImportExportResultSummarizer(
            $router,
            $configManager,
            $render,
            $managerRegistry
        );

        list($subject, $summary) = $consolidateService->getSummaryResultForNotification(
            $job,
            'import.csv',
            ImportExportResultSummarizer::TEMPLATE_IMPORT_RESULT
        );
        $this->assertEquals('subject', $subject);
        $this->assertEquals('summary', $summary);
    }

    public function testShouldReturnErrorLog()
    {
        $data = [
            'success' => true,
            'errors' => [
                'error 1',
            ],
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
        $childJob2->setData($data);
        $job->addChildJob($childJob2);

        $consolidateService = new ImportExportResultSummarizer(
            $this->createRouterMock(),
            $this->createConfigManagerMock(),
            $this->createRenderMock(),
            $this->createManagerRegistryMock()
        );
        $summary = $consolidateService->getErrorLog($job);

        $this->assertEquals("error in part #1: error 1\n\rerror in part #2: error 1\n\r", $summary);
    }

    public function testProcessExportData()
    {
        $exportResult = [
            'success' => true,
            'url' => '127.0.0.1',
            'readsCount' => 10,
            'errorsCount' => 0,
            'entities' => 'Test'
        ];


        $template = new EmailTemplate();
        $repository = $this->createEmailTemplateRepsitoryMock();
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => ImportExportResultSummarizer::TEMPLATE_EXPORT_RESULT])
            ->willReturn($template)
        ;
        $objectManager = $this->createObjectManagerMock();
        $objectManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($repository)
        ;

        $managerRegistry = $this->createManagerRegistryMock();
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(EmailTemplate::class)
            ->willReturn($objectManager)
        ;

        $render = $this->createRenderMock();
        $render
            ->expects($this->once())
            ->method('compileMessage')
            ->with($template, ['exportResult' => $exportResult, 'jobName' => 'test.job.name'])
            ->willReturn(['subject', 'summary'])
        ;

        $consolidateService = new ImportExportResultSummarizer(
            $this->createRouterMock(),
            $this->createConfigManagerMock(),
            $render,
            $managerRegistry
        );

        list($subject, $summary) = $consolidateService->processSummaryExportResultForNotification(
            'test.job.name',
            $exportResult
        );

        $this->assertEquals('subject', $subject);
        $this->assertEquals('summary', $summary);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Router
     */
    private function createRouterMock()
    {
        return $this->createMock(Router::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    private function createConfigManagerMock()
    {
        return $this->createMock(ConfigManager::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EmailRenderer
     */
    private function createRenderMock()
    {
        return $this->createMock(EmailRenderer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    private function createManagerRegistryMock()
    {
        return $this->createMock(ManagerRegistry::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    private function createObjectManagerMock()
    {
        return $this->createMock(ObjectManager::class);
    }
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EmailTemplateRepository
     */
    private function createEmailTemplateRepsitoryMock()
    {
        return $this->createMock(EmailTemplateRepository::class);
    }
}
