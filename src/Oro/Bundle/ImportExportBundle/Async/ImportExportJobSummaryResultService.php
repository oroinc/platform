<?php
namespace Oro\Bundle\ImportExportBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\MessageQueueBundle\Entity\Job;

class ImportExportJobSummaryResultService
{
    const TEMPLATE_IMPORT_RESULT = 'import_result';
    const TEMPLATE_IMPORT_VALIDATION_RESULT = 'import_validation_result';
    const TEMPLATE_EXPORT_RESULT = 'export_result';

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var EmailRenderer
     */
    protected $renderer;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param Router $router
     * @param ConfigManager $configManager
     * @param EmailRenderer $renderer,
     * @param ManagerRegistry $managerRegistry,
     */
    public function __construct(
        Router $router,
        ConfigManager $configManager,
        EmailRenderer $renderer,
        ManagerRegistry $managerRegistry
    ) {
        $this->router = $router;
        $this->configManager = $configManager;
        $this->renderer = $renderer;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param string $emailTemplateName
     *
     * @return null|EmailTemplateInterface
     */
    protected function findEmailTemplateByName($emailTemplateName)
    {
        return $this->managerRegistry
            ->getManagerForClass(EmailTemplate::class)
            ->getRepository(EmailTemplate::class)
            ->findOneBy(['name' => $emailTemplateName]);
    }

    /**
     * @param Job $job
     * @param $fileName
     * @param $template
     * @return array |[$body, $subject]
     */
    public function getSummaryResultForNotification(Job $job, $fileName, $template)
    {
        $job = $job->isRoot() ? $job : $job->getRootJob();
        $data = $this->getImportResultAsArray($job);
        $data['fileName'] = $fileName;
        $data['downloadLogUrl'] = $this->getImportDownloadLogUrl($job->getId());
        $emailTemplate = $this->findEmailTemplateByName($template);

        return $this->renderer->compileMessage($emailTemplate, ['data' => $data]);
    }

    /**
     * @param string $jobUniqueName
     * @param array $exportResult
     * @return array |[$body, $subject]
     */
    public function processSummaryExportResultForNotification($jobUniqueName, array $exportResult)
    {
        $emailTemplate = $this->findEmailTemplateByName(self::TEMPLATE_EXPORT_RESULT);

        return $this->renderer->compileMessage(
            $emailTemplate,
            ['exportResult' => $exportResult, 'jobName' => $jobUniqueName,]
        );
    }

    public function getErrorLog(Job $job)
    {
        $errorLog = null;
        $i = 0;
        foreach ($job->getChildJobs() as $childrenJob) {
            $childrenJobData = $childrenJob->getData();
            if (empty($childrenJobData)) {
                continue;
            }
            $i++;
            foreach ($childrenJobData['errors'] as $errorMessage) {
                $errorLog .= sprintf("error in part #%s: %s\n\r", $i, $errorMessage);
            }
        }
        return $errorLog;
    }

    protected function getImportDownloadLogUrl($jobId)
    {
        $url = $this->configManager->get('oro_ui.application_url') . $this->router->generate(
            'oro_importexport_import_error_log',
            ['jobId' => $jobId]
        );

        return $url;
    }

    /**
     * @param Job $job
     * @return array
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getImportResultAsArray(Job $job)
    {
        $data = [];
        $data['hasError'] = false;
        $data['successParts'] = 0;
        $data['totalParts'] = 0;
        $data['errors'] = 0;
        $data['process'] = 0;
        $data['read'] = 0;
        $data['add'] = 0;
        $data['replace'] = 0;
        $data['update'] = 0;
        $data['delete'] = 0;
        $data['error_entries'] = 0;

        foreach ($job->getChildJobs() as $childrenJob) {
            $childrenJobData = $childrenJob->getData();
            if (empty($childrenJobData)) {
                continue;
            }
            $data['successParts'] += (int)$childrenJobData['success'];
            $data['totalParts'] += 1;
            $totalDataImportJob = $childrenJobData['counts'];
            if (count($childrenJobData['errors'])) {
                $data['hasError'] = true;
            }
            $data['errors'] += isset($totalDataImportJob['errors']) ? $totalDataImportJob['errors'] : 0;
            $data['process'] += isset($totalDataImportJob['process']) ? $totalDataImportJob['process'] : 0;
            $data['read'] += isset($totalDataImportJob['read']) ? $totalDataImportJob['read'] : 0;
            $data['add'] += isset($totalDataImportJob['add']) ? $totalDataImportJob['add'] : 0;
            $data['replace'] += isset($totalDataImportJob['replace']) ? $totalDataImportJob['replace'] : 0;
            $data['update'] += isset($totalDataImportJob['update']) ? $totalDataImportJob['update'] : 0;
            $data['delete'] += isset($totalDataImportJob['delete']) ? $totalDataImportJob['delete'] : 0;
            $data['error_entries'] += isset($totalDataImportJob['error_entries']) ?
                $totalDataImportJob['error_entries'] : 0;
        }

        return $data;
    }
}
