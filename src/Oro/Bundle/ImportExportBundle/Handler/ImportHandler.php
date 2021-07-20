<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Reader\AbstractFileReader;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Component\MessageQueue\Exception\JobRedeliveryException;

/**
 * Handles import, checks whatever job was successful otherwise fills appropriate errors array
 */
class ImportHandler extends AbstractHandler
{
    /**
     * @var array
     */
    protected $configurationOptions = [];

    /**
     * @var string
     */
    protected $importingFileName;

    /**
     * @throws InvalidArgumentException
     * @throws JobRedeliveryException
     */
    public function handle(string $process, string $jobName, string $processorAlias, array $options = []): array
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

    public function splitImportFile(string $jobName, string $processorType, FileStreamWriter $writer): array
    {
        $step = $this->getJobStep($jobName, $processorType);
        $reader = $step->getReader();

        if (! $reader instanceof AbstractFileReader) {
            throw new LogicException('Reader must be instance of AbstractFileReader');
        }
        $this->batchFileManager->setReader($reader);
        $this->batchFileManager->setWriter($writer);

        $options = [];
        $batchSize = $step instanceof ItemStep ? $step->getBatchSize() : null;
        if ($batchSize) {
            $options[Context::OPTION_BATCH_SIZE] = $batchSize;
        }

        $this->batchFileManager->setConfigurationOptions(array_merge($this->configurationOptions, $options));

        return $this->batchFileManager->splitFile($this->importingFileName);
    }

    /**
     * Handles import validation action
     *
     * @param string $jobName
     * @param string $processorAlias
     * @param array  $options
     *
     * @return array response parameters
     */
    public function handleImportValidation(string $jobName, string $processorAlias, array $options = []): array
    {
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
            'success'        => $this->isSuccessful($counts, $jobResult),
            'processorAlias' => $processorAlias,
            'counts'         => $counts,
            'errors'         => $errors,
            'entityName'     => $entityName,
            'options'        => $options,
            'postponedRows'  => $jobResult->getContext()->getPostponedRows(),
            'postponedDelay' => $jobResult->getContext()->getValue('postponedRowsDelay')
        ];
    }

    /**
     * Handles import action
     *
     * @throws JobRedeliveryException
     */
    public function handleImport(string $jobName, string $processorAlias, array $options = []): array
    {
        $jobResult = $this->executeJob($jobName, $processorAlias, $options);

        if ($jobResult->needRedelivery()) {
            throw JobRedeliveryException::create();
        }

        $counts = $this->getValidationCounts($jobResult);
        $importInfo = '';

        $errors = [];
        if (!empty($counts['errors'])) {
            $errors = $this->getErrors($jobResult);
        }

        $isSuccessful = $this->isSuccessful($counts, $jobResult);
        if ($isSuccessful) {
            $message = $this->translator->trans('oro.importexport.import.success');

            $entityName = $this->processorRegistry->getProcessorEntityName(
                ProcessorRegistry::TYPE_IMPORT,
                $processorAlias
            );

            $importInfo = $this->getImportInfo($counts, $entityName);
        } else {
            $message = $this->translator->trans(
                'oro.importexport.import.error',
                ['%errors%' => json_encode($errors)]
            );
        }

        $context = $jobResult->getContext();

        return [
            'success' => $isSuccessful,
            'message' => $message,
            'importInfo' => $importInfo,
            'errors'  => $errors,
            'counts'  => $counts,
            'postponedRows' => $context->getPostponedRows(),
            'postponedDelay' => $context->getValue('postponedRowsDelay'),
            'deadlockDetected' => $context->getValue('deadlockDetected')
        ];
    }

    /**
     * @param string $fileName
     */
    public function setImportingFileName($fileName)
    {
        $this->importingFileName = $fileName;
    }

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
    private function executeJob($jobName, $processorAlias, array $options)
    {
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
                        'filePath' => $this->importingFileName
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
    private function getValidationCounts(JobResult $jobResult)
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
    private function executeValidation(
        $jobName,
        $processorAlias,
        array $options,
        $entityName
    ) {
        $configuration = [
            'import_validation' =>
                array_merge(
                    [
                        'processorAlias' => $processorAlias,
                        'entityName' => $entityName,
                        'filePath' => $this->importingFileName
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
    private function getImportInfo($counts, $entityName)
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

    /**
     * @param JobResult $jobResult
     * @return array
     */
    private function getErrors(JobResult $jobResult)
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

    private function isSuccessful(array $counts, JobResult $jobResult): bool
    {
        $processedCount = $counts['process'] ?? 0;
        $postponedCount = count($jobResult->getContext()->getPostponedRows());
        $isSuccessful = $jobResult->isSuccessful();
        if ($processedCount === 0 && $postponedCount === 0) {
            $isSuccessful = false;
        }

        return $isSuccessful;
    }
}
