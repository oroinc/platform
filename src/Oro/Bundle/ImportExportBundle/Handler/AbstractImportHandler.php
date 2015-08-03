<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Job\JobResult;

abstract class AbstractImportHandler extends AbstractHandler
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Handles import validation action
     *
     * @param string $jobName
     * @param string $processorAlias
     * @param string $inputFormat
     * @param string $inputFilePrefix
     * @param array  $options
     *
     * @return array response parameters
     */
    abstract public function handleImportValidation(
        $jobName,
        $processorAlias,
        $inputFormat = 'csv',
        $inputFilePrefix = null,
        array $options = []
    );

    /**
     * Handles import action
     *
     * @param string $jobName
     * @param string $processorAlias
     * @param string $inputFormat
     * @param string $inputFilePrefix
     * @param array  $options
     *
     * @return array
     */
    abstract public function handleImport(
        $jobName,
        $processorAlias,
        $inputFormat = 'csv',
        $inputFilePrefix = null,
        array $options = []
    );

    /**
     * @param $inputFormat
     * @param null $inputFilePrefix
     * @return string
     */
    abstract protected function getImportingFileName($inputFormat, $inputFilePrefix = null);

    /**
     * @param string $jobName
     * @param string $processorAlias
     * @param string $inputFormat
     * @param string $inputFilePrefix
     * @param array $options
     *
     * @return JobResult
     */
    protected function executeJob($jobName, $processorAlias, $inputFormat, array $options, $inputFilePrefix = null)
    {
        $fileName = $this->getImportingFileName($inputFormat, $inputFilePrefix);
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
     * @param $inputFormat
     * @param $inputFilePrefix
     * @param array $options
     * @param $entityName
     * @return JobResult
     */
    protected function executeValidation(
        $jobName,
        $processorAlias,
        $inputFormat,
        $inputFilePrefix,
        array $options,
        $entityName
    ) {
        $fileName = $this->getImportingFileName($inputFormat, $inputFilePrefix);
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
