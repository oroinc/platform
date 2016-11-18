<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Symfony\Component\Routing\RouterInterface;

class HttpImportHandler extends AbstractImportHandler
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param RouterInterface $router
     */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

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

        $errorsUrl           = null;
        $errorsAndExceptions = [];
        $context = $jobResult->getContext();
        if (!empty($counts['errors'])) {
            $errorsUrl = $this->router->generate(
                'oro_importexport_error_log',
                ['jobCode' => $jobResult->getJobCode()]
            );

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
            'errorsUrl'      => $errorsUrl,
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

        $errorsUrl = null;
        if ($jobResult->getFailureExceptions()) {
            $errorsUrl = $this->router->generate(
                'oro_importexport_error_log',
                ['jobCode' => $jobResult->getJobCode()]
            );
        }

        return [
            'success'    => $jobResult->isSuccessful(),
            'message'    => $message,
            'importInfo' => $importInfo,
            'errorsUrl'  => $errorsUrl,
        ];
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
