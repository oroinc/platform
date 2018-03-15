<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Exception\JobRedeliveryException;

/**
 * Handles import, checks whatever job was successful otherwise fills appropriate errors array
 */
class HttpImportHandler extends AbstractImportHandler
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

        $errorsAndExceptions = [];
        $context = $jobResult->getContext();
        if (!empty($counts['errors'])) {
            $contextErrors = [];
            if ($context) {
                $contextErrors = $context->getErrors();
            }
            $errorsAndExceptions = array_slice(
                array_merge($jobResult->getFailureExceptions(), $contextErrors),
                0,
                100
            );
        }
        if ($context && $context instanceof StepExecutionProxyContext) {
            // each warning is for an invalid item
            $countWarnings = count($context->getWarnings());
            $counts['error_entries'] += $countWarnings;
        }

        return [
            'success'        => $jobResult->isSuccessful() && isset($counts['process']) && $counts['process'] > 0,
            'processorAlias' => $processorAlias,
            'counts'         => $counts,
            'errors'         => $errorsAndExceptions,
            'entityName'     => $entityName,
            'options'        => $options
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

        if ($jobResult->needRedelivery()) {
            throw JobRedeliveryException::create();
        }

        $counts = $this->getValidationCounts($jobResult);
        $importInfo = '';

        if ($jobResult->isSuccessful()) {
            $message = $this->translator->trans('oro.importexport.import.success');

            $entityName = $this->processorRegistry->getProcessorEntityName(
                ProcessorRegistry::TYPE_IMPORT,
                $processorAlias
            );

            $importInfo = $this->getImportInfo($counts, $entityName);
        } else {
            $message = $this->translator->trans('oro.importexport.import.error');
        }

        $errors = [];
        if ($context = $jobResult->getContext()) {
            $errors = $context->getErrors();
        }
        if ($jobResult->getFailureExceptions()) {
            $errors = array_merge($errors, $jobResult->getFailureExceptions());
        }

        return [
            'success'    => $jobResult->isSuccessful(),
            'message'    => $message,
            'importInfo' => $importInfo,
            'errors'     => $errors,
            'counts'     => $counts,
            'postponedRows' => $jobResult->getContext()->getPostponedRows(),
            'deadlockDetected' => $jobResult->getContext()->getValue('deadlockDetected')
        ];
    }
}
