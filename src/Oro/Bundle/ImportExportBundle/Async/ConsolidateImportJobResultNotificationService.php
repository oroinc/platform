<?php
namespace Oro\Bundle\ImportExportBundle\Async;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job;

class ConsolidateImportJobResultNotificationService
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param TranslatorInterface $translator
     * @param Router $router
     * @param ConfigManager $configManager
     */
    public function __construct(TranslatorInterface $translator, Router $router, ConfigManager $configManager)
    {
        $this->translator = $translator;
        $this->router = $router;
        $this->configManager = $configManager;
    }

    /**
     * @param Job $job
     * @param $fileName
     * @return string
     */
    public function getImportSummary(Job $job, $fileName)
    {
        $job = $job->isRoot() ? $job : $job->getRootJob();
        $data = $this->getImportResultAsArray($job);
        $downloadErrorLog = '';
        if ($data['hasError']) {
            $downloadErrorLog = $this->getImportDownloadLogUrl($job->getId());
        }

        return $this->translator->trans(
            'oro.importexport.import.notification.result',
            [
                '%file_name%' => $fileName,
                '%success_parts%' => $data['successParts'],
                '%total_parts%' => $data['totalParts']
            ]
        ). PHP_EOL . $this->translator->trans(
            'oro.importexport.import.notification.detail',
            [
                '%errors%' => $data['errors'],
                '%process%' => $data['process'],
                '%read%' => $data['read'],
                '%add%' => $data['add'],
                '%update%' => $data['update'],
                '%replace%' => $data['replace'],
            ]
        ) . $downloadErrorLog;
    }

    protected function getImportDownloadLogUrl($jobId)
    {
        $fileName = $this->translator->trans('oro.importexport.import.download_error_log');
        $url = $this->configManager->get('oro_ui.application_url') . $this->router->generate(
            'oro_importexport_import_error_log',
            ['jobId' => $jobId]
        );

        return sprintf(
            '<br/><a href="%s" target="_blank">%s</a>',
            $url,
            $fileName
        );
    }

    /**
     * @param Job $job
     * @param $fileName
     * @return string
     */
    public function getValidationImportSummary(Job $job, $fileName)
    {
        $downloadErrorLog = '';
        $job = $job->isRoot() ? $job : $job->getRootJob();
        $data = $this->getImportResultAsArray($job);
        if ($data['hasError']) {
            $downloadErrorLog = $this->getImportDownloadLogUrl($job->getId());
        }

        return $this->translator->trans(
            'oro.importexport.import.validation.notification.result',
            [
                '%file_name%' => $fileName,
                '%success_parts%' => $data['successParts'],
                '%total_parts%' => $data['totalParts']
            ]
        ) .PHP_EOL .
            $this->translator->trans(
                'oro.importexport.import.validation.notification.detail',
                [
                    '%errors%' => $data['errors'],
                    '%process%' => $data['process'],
                    '%read%' => $data['read'],
                ]
            ) . $downloadErrorLog;
    }

    public function getErrorLog(Job $job)
    {
        $errorLog =  null;
        foreach ($job->getChildJobs() as $key => $childrenJob) {
            $childrenJobData = $childrenJob->getData();
            if (empty($childrenJobData)) {
                continue;
            }
            foreach ($childrenJobData['errors'] as $errorMessage) {
                $errorLog .= sprintf("error in part #%s: %s\n\r", ++$key, $errorMessage);
            }
        }
        return $errorLog;
    }

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
        /**
         * @var $childrenJob Job
         */
        foreach ($job->getChildJobs() as $childrenJob) {
            $childrenJobData = $childrenJob->getData();
            if (empty($childrenJobData)) {
                continue;
            }
            $data['successParts'] += (int)$childrenJobData['success'];
            $data['totalParts'] += 1;
            $totalDataImportJob = $childrenJobData['counts'];
            if  (count($childrenJobData['errors'])) {
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
