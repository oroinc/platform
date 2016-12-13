<?php
namespace Oro\Bundle\ImportExportBundle\Async;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Symfony\Component\Translation\TranslatorInterface;

class ConsolidateImportJobResultNotificationService
{
    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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

        return $this->translator->trans(
            'oro.importexport.import.notification.result',
            [
                '%file_name%' => $fileName,
                '%success_parts%' => $data['successParts'],
                '%total_parts%' => $data['totalParts']
            ]
        ) .PHP_EOL .
            $this->translator->trans(
                'oro.importexport.import.notification.detail',
                [
                    '%errors%' => $data['errors'],
                    '%process%' => $data['process'],
                    '%read%' => $data['read'],
                    '%add%' => $data['add'],
                    '%update%' => $data['update'],
                    '%replace%' => $data['replace'],
                ]
            ) .PHP_EOL .PHP_EOL . $data['errorMessages'];
    }

    /**
     * @param Job $job
     * @param $fileName
     * @return string
     */
    public function getValidationImportSummary(Job $job, $fileName)
    {
        $job = $job->isRoot() ? $job : $job->getRootJob();
        $data = $this->getImportResultAsArray($job);

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
            ) .PHP_EOL .PHP_EOL . $data['errorMessages'];
    }


    protected function getImportResultAsArray(Job $job)
    {
        $data = [];
        $data['errorMessages'] = '';
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
            foreach ($childrenJobData['errors'] as $errorMessage) {
                $data['errorMessages'] .= sprintf("%s: %s\n\r", $childrenJobData['fileName'], $errorMessage);
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