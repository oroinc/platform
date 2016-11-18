<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

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

        $counts = $this->getValidationCounts($jobResult);

        $errors = [];
        if (!empty($counts['errors'])) {
            $errors = $this->getErrors($jobResult);
        }

        $isSuccessful = $jobResult->isSuccessful() && isset($counts['process']) && $counts['process'] > 0;

        $message = $isSuccessful
            ? $this->translator->trans('oro.importexport.import.success')
            : $this->translator->trans('oro.importexport.import.error');

        return [
            'success' => $isSuccessful,
            'counts'  => $counts,
            'errors'  => $errors,
            'message' => $message,
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
