<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

class CliImportHandler extends AbstractImportHandler
{
    /**
     * @var string
     */
    protected $importingFileName;

    /**
     * {@inheritdoc}
     */
    public function handleImportValidation(
        $jobName,
        $processorAlias,
        $inputFormat = 'csv',
        $inputFilePrefix = null,
        array $options = []
    ) {
        if ($inputFilePrefix === null) {
            $inputFilePrefix = $processorAlias;
        }
        $entityName = $this->processorRegistry->getProcessorEntityName(
            ProcessorRegistry::TYPE_IMPORT_VALIDATION,
            $processorAlias
        );

        $jobResult = $this->executeValidation(
            $jobName,
            $processorAlias,
            $inputFormat,
            $inputFilePrefix,
            $options,
            $entityName
        );

        $counts = $this->getValidationCounts($jobResult);

        $errorsAndExceptions = [];
        if (!empty($counts['errors'])) {
            $context = $jobResult->getContext();
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

        return [
            'success'        => $jobResult->isSuccessful() && isset($counts['process']) && $counts['process'] > 0,
            'processorAlias' => $processorAlias,
            'counts'         => $counts,
            'errors'         => $errorsAndExceptions,
            'entityName'     => $entityName,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function handleImport(
        $jobName,
        $processorAlias,
        $inputFormat = 'csv',
        $inputFilePrefix = null,
        array $options = []
    ) {
        $jobResult = $this->executeJob($jobName, $processorAlias, $inputFormat, $options);

        $message = $jobResult->isSuccessful()
            ? $this->translator->trans('oro.importexport.import.success')
            : $this->translator->trans('oro.importexport.import.error');

        return [
            'success' => $jobResult->isSuccessful(),
            'message' => $message,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getImportingFileName($inputFormat, $inputFilePrefix = null)
    {
        return $this->importingFileName;
    }

    /**
     * @param string $fileName
     */
    public function setImportingFileName($fileName)
    {
        $this->importingFileName = $fileName;
    }
}
