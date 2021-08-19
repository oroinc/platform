<?php

namespace Oro\Bundle\ImportExportBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Gaufrette\Exception\FileNotFound;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Prepared import and export data by forming a single structure for future use
 */
class ImportExportResultSummarizer
{
    const TEMPLATE_IMPORT_RESULT = 'import_result';
    const TEMPLATE_IMPORT_VALIDATION_RESULT = 'import_validation_result';
    const TEMPLATE_EXPORT_RESULT = 'export_result';
    const TEMPLATE_IMPORT_ERROR = 'import_error';

    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        ConfigManager $configManager,
        FileManager $fileManager,
        ManagerRegistry $registry
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->configManager = $configManager;
        $this->fileManager = $fileManager;
        $this->registry = $registry;
    }

    /**
     * @param Job $job
     * @param string $fileName
     *
     * @return array
     */
    public function getSummaryResultForNotification(Job $job, $fileName)
    {
        $job = $job->isRoot() ? $job : $job->getRootJob();

        $data = $this->getImportResultAsArray($job);
        $data['fileName'] = $fileName;
        $data['downloadLogUrl'] = $this->getDownloadErrorLogUrl($job->getId());

        return ['data' => $data];
    }

    /**
     * @param Job $job
     * @param string $fileName
     *
     * @return array
     */
    public function processSummaryExportResultForNotification(Job $job, $fileName)
    {
        $job = $job->isRoot() ? $job : $job->getRootJob();
        $data = $this->getExportResultAsArray($job);
        $data['fileName'] = $fileName;

        $data['url'] = $this->getDownloadUrl($job->getId());
        $data['downloadLogUrl'] = $this->getDownloadErrorLogUrl($job->getId());
        if (!$data['success']) {
            $data['url'] = $data['downloadLogUrl'];
        }

        return ['exportResult' => $data, 'jobName' => $job->getName()];
    }

    /**
     * @param Job $job
     *
     * @return null|string
     * @throws FileNotFound
     */
    public function getErrorLog(Job $job)
    {
        $errorLog = null;
        $job = $job->isRoot() ? $job : $job->getRootJob();

        $repo = $this->registry->getRepository(Job::class);
        foreach ($repo->getChildJobErrorLogFiles($job) as $childrenJobData) {
            $fileName = $childrenJobData['error_log_file'];

            if (!$this->fileManager->isFileExist($fileName)) {
                $errorLog .= sprintf('Log file of job id: "%s" was not found.', $childrenJobData['id']) . PHP_EOL;
                continue;
            }

            $errorLog .= implode(
                PHP_EOL,
                json_decode($this->fileManager->getContent($fileName), true)
            ) . PHP_EOL;
        }

        return $errorLog;
    }

    /**
     * @param integer $jobId
     *
     * @return string
     */
    protected function getDownloadErrorLogUrl($jobId)
    {
        return
            $this->configManager->get('oro_ui.application_url')
            . $this->urlGenerator->generate('oro_importexport_job_error_log', ['jobId' => $jobId]);
    }

    /**
     * @param integer $jobId
     *
     * @return string
     */
    protected function getDownloadUrl($jobId)
    {
        return sprintf(
            '%s%s',
            $this->configManager->get('oro_ui.application_url'),
            $this->urlGenerator->generate('oro_importexport_export_download', ['jobId' => $jobId])
        );
    }

    /**
     * @param Job $job
     *
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

        $data['hasError'] = !!$data['errors'];

        return $data;
    }

    /**
     * @param Job $job
     *
     * @return array
     */
    protected function getExportResultAsArray(Job $job)
    {
        $data = [];
        $data['readsCount'] = 0;
        $data['errorsCount'] = 0;
        $data['entities'] = null;
        $data['success'] = 0;

        foreach ($job->getChildJobs() as $childrenJob) {
            $childrenJobData = $childrenJob->getData();
            if (empty($childrenJobData)) {
                continue;
            }

            $data['readsCount'] += $childrenJobData['readsCount'];
            $data['errorsCount'] += $childrenJobData['errorsCount'];
            $data['success'] += $childrenJobData['success'];
            $data['entities'] = isset($childrenJobData['entities']) ? $childrenJobData['entities'] : $data['entities'];
        }

        $data['success'] = !!$data['success'];

        return $data;
    }

    /**
     * @param array $data
     * @param string $process
     * @param LoggerInterface $logger
     *
     * @return string
     */
    public function getImportSummaryMessage(array $data, $process, LoggerInterface $logger)
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
                $message = sprintf(
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
