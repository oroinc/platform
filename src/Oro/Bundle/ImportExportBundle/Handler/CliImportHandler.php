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
     * Handles import validation action
     *
     * @param string $jobName
     * @param string $processorAlias
     * @param string $inputFormat
     * @param string $inputFilePrefix
     * @param array $options
     * @return array response parameters
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

        $errorsAndExceptions = array();
        if (!empty($counts['errors'])) {
            $context = $jobResult->getContext();
            $errorsAndExceptions = array_slice(
                array_merge($jobResult->getFailureExceptions(), $context->getErrors()),
                0,
                100
            );
        }

        return array(
            'isSuccessful'   => $jobResult->isSuccessful() && isset($counts['process']) && $counts['process'] > 0,
            'processorAlias' => $processorAlias,
            'counts'         => $counts,
            'errors'         => $errorsAndExceptions,
            'entityName'     => $entityName,
        );
    }

    /**
     * Handles import action
     *
     * @param string $jobName
     * @param string $processorAlias
     * @param string $inputFormat
     * @param string $inputFilePrefix
     * @param array $options
     * @return array
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

        return array(
            'success' => $jobResult->isSuccessful(),
            'message' => $message,
        );
    }

    /**
     * @param $inputFormat
     * @param null $inputFilePrefix
     * @return string
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
