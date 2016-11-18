<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

abstract class AbstractImportHandler extends AbstractHandler
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
     * @param array  $options
     *
     * @return array response parameters
     */
    abstract public function handleImportValidation(
        $jobName,
        $processorAlias,
        array $options = []
    );

    /**
     * Handles import action
     *
     * @param string $jobName
     * @param string $processorAlias
     * @param array  $options
     *
     * @return array
     */
    abstract public function handleImport(
        $jobName,
        $processorAlias,
        array $options = []
    );

    /**
     * @return string
     */
    protected function getImportingFileName()
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

    /**
     * @param string $jobName
     * @param string $processorAlias
     * @param array $options
     *
     * @return JobResult
     */
    protected function executeJob($jobName, $processorAlias, array $options)
    {
        $fileName = $this->getImportingFileName();
        $entityName = $this->processorRegistry->getProcessorEntityName(
            ProcessorRegistry::TYPE_IMPORT,
            $processorAlias
        );

        $configuration = [
            'import' =>
                array_merge(
                    [
                        'processorAlias' => $processorAlias,
                        'entityName' => $entityName,
                        'filePath' => $fileName
                    ],
                    $options
                )
        ];

        $jobResult = $this->jobExecutor->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            $jobName,
            $configuration
        );

        return $jobResult;
    }

    /**
     * @param JobResult $jobResult
     * @return array
     */
    protected function getValidationCounts(JobResult $jobResult)
    {
        $context = $jobResult->getContext();

        $counts = [];
        $counts['errors'] = count($jobResult->getFailureExceptions());
        if ($context) {
            $counts['process'] = 0;
            $counts['read'] = $context->getReadCount();
            $counts['process'] += $counts['add'] = $context->getAddCount();
            $counts['process'] += $counts['replace'] = $context->getReplaceCount();
            $counts['process'] += $counts['update'] = $context->getUpdateCount();
            $counts['process'] += $counts['delete'] = $context->getDeleteCount();
            $counts['error_entries'] = $context->getErrorEntriesCount();
            $counts['errors'] += count($context->getErrors());

            return $counts;
        }

        return $counts;
    }

    /**
     * @param $jobName
     * @param $processorAlias
     * @param array $options
     * @param $entityName
     * @return JobResult
     */
    protected function executeValidation(
        $jobName,
        $processorAlias,
        array $options,
        $entityName
    ) {
        $fileName = $this->getImportingFileName();
        $configuration = [
            'import_validation' =>
                array_merge(
                    [
                        'processorAlias' => $processorAlias,
                        'entityName' => $entityName,
                        'filePath' => $fileName
                    ],
                    $options
                )
        ];

        $isValidationMode = $this->jobExecutor->isValidationMode();
        $this->jobExecutor->setValidationMode(true);
        $jobResult = $this->jobExecutor->executeJob(
            ProcessorRegistry::TYPE_IMPORT_VALIDATION,
            $jobName,
            $configuration
        );
        $this->jobExecutor->setValidationMode($isValidationMode);

        return $jobResult;
    }
}
