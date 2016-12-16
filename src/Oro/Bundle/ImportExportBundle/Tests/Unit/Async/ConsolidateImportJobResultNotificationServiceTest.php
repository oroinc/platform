<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Oro\Bundle\ImportExportBundle\Async\ConsolidateImportJobResultNotificationService;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class ConsolidateImportJobResultNotificationServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testCanBeConstructedWithRequiredAttributes()
    {
        new ConsolidateImportJobResultNotificationService(
            $this->createTranslatorInterfaceMock(),
            $this->createRouterMock(),
            $this->createConfigManagerMock()
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

        $job = new Job();
        $childJob1 = new Job();
        $childJob1->setData($data);
        $job->addChildJob($childJob1);
        $childJob2 = new Job();
        $childJob2->setData($data);
        $job->addChildJob($childJob2);

        $translator = $this->createTranslatorInterfaceMock();
        $translator
            ->expects($this->at(0))
            ->method('trans')
            ->with(
                'oro.importexport.import.notification.result',
                [
                    '%file_name%' => 'import.csv',
                    '%success_parts%' => 2,
                    '%total_parts%' => 2
                ]
            )
            ->willReturn('info')
        ;
        $translator
            ->expects($this->at(1))
            ->method('trans')
            ->with(
                'oro.importexport.import.notification.detail',
                [
                    '%errors%' => 0,
                    '%process%' => 14,
                    '%read%' => 14,
                    '%add%' => 4,
                    '%update%' => 0,
                    '%replace%' => 10,
                ]
            )
            ->willReturn('detail info')
        ;

        $consolidateService = new ConsolidateImportJobResultNotificationService(
            $translator,
            $this->createRouterMock(),
            $this->createConfigManagerMock()
        );
        $summary = $consolidateService->getImportSummary($job, 'import.csv');
        $this->assertEquals("info\ndetail info", $summary);
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

        $job = new Job();
        $job->setId(1);
        $childJob = new Job();
        $childJob->setData($data);
        $job->addChildJob($childJob);

        $translator = $this->createTranslatorInterfaceMock();
        $translator
            ->expects($this->at(0))
            ->method('trans')
            ->with('oro.importexport.import.download_error_log')
            ->willReturn('error log')
        ;
        $translator
            ->expects($this->at(1))
            ->method('trans')
            ->with(
                'oro.importexport.import.notification.result',
                [
                    '%file_name%' => 'import.csv',
                    '%success_parts%' => 1,
                    '%total_parts%' => 1
                ]
            )
            ->willReturn('info')
        ;
        $translator
            ->expects($this->at(2))
            ->method('trans')
            ->with(
                'oro.importexport.import.notification.detail',
                [
                    '%errors%' => 2,
                    '%process%' => 5,
                    '%read%' => 5,
                    '%add%' => 2,
                    '%update%' => 0,
                    '%replace%' => 1,
                ]
            )
            ->willReturn('details')
        ;

        $configManager = $this->createConfigManagerMock();
        $configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn('http://localhost/')
        ;

        $router = $this->createRouterMock();
        $router
            ->expects($this->once())
            ->method('generate')
            ->with(
                'oro_importexport_import_error_log',
                ['jobId' => 1]
            )
            ->willReturn('download/1.log')
        ;

        $consolidateService = new ConsolidateImportJobResultNotificationService(
            $translator,
            $router,
            $configManager
        );
        $summary = $consolidateService->getImportSummary($job, 'import.csv');

        $this->assertEquals(
            "info\ndetails<br/><a href=\"http://localhost/download/1.log\" target=\"_blank\">error log</a>",
            $summary
        );
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

        $consolidateService = new ConsolidateImportJobResultNotificationService(
            $this->createTranslatorInterfaceMock(),
            $this->createRouterMock(),
            $this->createConfigManagerMock()
        );
        $summary = $consolidateService->getErrorLog($job);

        $this->assertEquals("error in part #1: error 1\n\rerror in part #2: error 1\n\r", $summary);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    private function createTranslatorInterfaceMock()
    {
        return $this->getMock(TranslatorInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Router
     */
    private function createRouterMock()
    {
        return $this->getMock(Router::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    private function createConfigManagerMock()
    {
        return $this->getMock(ConfigManager::class, [], [], '', false);
    }
}
