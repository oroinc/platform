<?php
namespace Oro\Bundle\ImportExportBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;

use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Router;

class ImportExportResultSummarizer
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
     * @return EmailTemplateInterface
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

//        TODO refactor in https://magecore.atlassian.net/browse/BAP-13215
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
            ['exportResult' => $exportResult, 'jobName' => $jobUniqueName, ]
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

    public function getSummaryMessage(array $data, $process, LoggerInterface $logger)
    {
        switch ($process) {
            case ProcessorRegistry::TYPE_IMPORT:
                $message = sprintf(
                    'Import of the %s is completed, success: %s, info: %s, message: %s',
                    $data['originFileName'],
                    $data['success'],
                    $data['importInfo'],
                    $data['message']
                );
                break;
            case ProcessorRegistry::TYPE_IMPORT_VALIDATION:
                $message =  sprintf(
                    'Import validation of the %s from %s is completed.
                    Success: %s.
                    Info: %s.
                    Errors: %s',
                    $data['originFileName'],
                    $data['entityName'],
                    $data['success'] ? 'true' : 'false',
                    json_encode($data['counts']),
                    json_encode($data['errors'])
                );
                break;
            default:
                $message = sprintf('The Process "%s" is not supported.', $process);
                $logger->error($message, ['dataResult' => $data, 'process' => $process]);
                break;
        }

        return $message;
    }
}
