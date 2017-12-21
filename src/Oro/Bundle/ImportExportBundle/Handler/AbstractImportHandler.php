<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Reader\AbstractFileReader;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;

abstract class AbstractImportHandler extends AbstractHandler
{
    /**
     * @var array
     */
    protected $configurationOptions = [];

    /**
     * @param string $process
     * @param string $jobName
     * @param string $processorAlias
     * @param array $options
     */
    public function handle($process, $jobName, $processorAlias, array $options = [])
    {
        switch ($process) {
            case ProcessorRegistry::TYPE_IMPORT:
                return $this->handleImport($jobName, $processorAlias, $options);
                break;
            case ProcessorRegistry::TYPE_IMPORT_VALIDATION:
                return $this->handleImportValidation($jobName, $processorAlias, $options);
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf('Not Found method for handle of "%s" process.', $process)
                );
        }
    }

    /**
     * @param string $jobName
     * @param string $processorType
     * @param FileStreamWriter $writer
     * @return array
     */
    public function splitImportFile($jobName, $processorType, FileStreamWriter $writer)
    {
        $reader = $this->getJobReader($jobName, $processorType);

        if (! $reader instanceof AbstractFileReader) {
            throw new LogicException('Reader must be instance of AbstractFileReader');
        }
        $this->batchFileManager->setReader($reader);
        $this->batchFileManager->setWriter($writer);
        $this->batchFileManager->setConfigurationOptions($this->configurationOptions);

        return $this->batchFileManager->splitFile($this->getImportingFileName());
    }

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
     * @param array $options
     */
    public function setConfigurationOptions(array $options)
    {
        $this->configurationOptions = $options;
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
            $counts['process'] += $counts['replace'] =  $context->getReplaceCount();
            $counts['process'] += $counts['update'] = $context->getUpdateCount();
            $counts['process'] += $counts['delete'] = $context->getDeleteCount();
            $counts['error_entries'] = $context->getErrorEntriesCount();
            $counts['errors'] += count($context->getErrors());
            // for cases when data wasn't imported
            if (! $jobResult->isSuccessful()) {
                $counts['add'] = $counts['replace'] = $counts['update'] = $counts['delete'] = 0;
            }
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

    /**
     * @param array $counts
     * @param string $entityName
     * @return string
     */
    protected function getImportInfo($counts, $entityName)
    {
        $add = 0;
        $update = 0;

        if (isset($counts['add'])) {
            $add += $counts['add'];
        }
        if (isset($counts['update'])) {
            $update += $counts['update'];
        }
        if (isset($counts['replace'])) {
            $update += $counts['replace'];
        }

        $importInfo = $this->translator->trans(
            'oro.importexport.import.alert',
            ['%added%' => $add, '%updated%' => $update, '%entities%' => $this->getEntityPluralName($entityName)]
        );

        return $importInfo;
    }
}
