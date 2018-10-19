<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

/**
 * Handles import, checks whatever job was successful otherwise fills errors array
 */
class CliImportHandler extends AbstractImportHandler
{
    /**
     * {@inheritdoc}
     */
    public function handleImportValidation(
        $jobName,
        $processorAlias,
        array $options = []
    ) {
        $entityName = $this->processorRegistry->getProcessorEntityName(
            ProcessorRegistry::TYPE_IMPORT_VALIDATION,
            $processorAlias
        );

        $jobResult = $this->executeValidation(
            $jobName,
            $processorAlias,
            $options,
            $entityName
        );

        $counts = $this->getValidationCounts($jobResult);

        $errors = [];
        if (!empty($counts['errors'])) {
            $errors = $this->getErrors($jobResult);
        }

        return [
            'success'        => $jobResult->isSuccessful() && isset($counts['process']) && $counts['process'] > 0,
            'processorAlias' => $processorAlias,
            'counts'         => $counts,
            'errors'         => $errors,
            'entityName'     => $entityName,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function handleImport(
        $jobName,
        $processorAlias,
        array $options = []
    ) {
        $jobResult = $this->executeJob($jobName, $processorAlias, $options);
        $entityName = '';
        $counts = $this->getValidationCounts($jobResult);

        $errors = [];
        if (!empty($counts['errors'])) {
            $errors = $this->getErrors($jobResult);
        }

        $isSuccessful = $jobResult->isSuccessful() && isset($counts['process']) && $counts['process'] > 0;

        if ($isSuccessful) {
            $entityName = $this->processorRegistry->getProcessorEntityName(
                ProcessorRegistry::TYPE_IMPORT,
                $processorAlias
            );
        }
        $message = $isSuccessful
            ? $this->translator->trans('oro.importexport.import.success')
            : $this->translator->trans('oro.importexport.import.error');

        return [
            'success' => $isSuccessful,
            'counts'  => $counts,
            'errors'  => $errors,
            'message' => $message,
            'importInfo' => $this->getImportInfo($counts, $entityName),
            'postponedRows' => $jobResult->getContext()->getPostponedRows(),
            'deadlockDetected' => $jobResult->getContext()->getValue('deadlockDetected')
        ];
    }

    /**
     * @param JobResult $jobResult
     * @return array
     */
    protected function getErrors(JobResult $jobResult)
    {
        $context = $jobResult->getContext();
        $contextErrors = [];
        if ($context) {
            $contextErrors = $context->getErrors();
        }
        return array_slice(
            array_merge($jobResult->getFailureExceptions(), $contextErrors),
            0,
            100
        );
    }
}
