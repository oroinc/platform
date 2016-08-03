<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;

class HttpImportHandler extends AbstractImportHandler
{
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param SessionInterface $session
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }

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
            $counts['invalid_entries'] = count($context->getWarnings());
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
        $inputFormat = 'csv',
        $inputFilePrefix = null,
        array $options = []
    ) {
        if ($inputFilePrefix === null) {
            $inputFilePrefix = $processorAlias;
        }

        $jobResult = $this->executeJob($jobName, $processorAlias, $inputFormat, $options, $inputFilePrefix);
        $counts = $this->getValidationCounts($jobResult);
        $importInfo = '';
        
        if ($jobResult->isSuccessful()) {
            $this->removeImportingFile($inputFormat, $inputFilePrefix);
            $message = $this->translator->trans('oro.importexport.import.success');
            $importInfo = $this->getImportInfo($counts);
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
     * Saves the given file in a temporary directory and remember the name of temporary file in a session
     *
     * @param File   $file
     * @param string $temporaryFilePrefix
     * @param string $temporaryFileExtension
     */
    public function saveImportingFile(File $file, $temporaryFilePrefix, $temporaryFileExtension)
    {
        $tmpFileName = $this->fileSystemOperator
            ->generateTemporaryFileName($temporaryFilePrefix, $temporaryFileExtension);
        $file->move(dirname($tmpFileName), basename($tmpFileName));

        $this->session->set(
            $this->getImportFileSessionKey($temporaryFilePrefix, $temporaryFileExtension),
            $tmpFileName
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getImportingFileName($inputFormat, $inputFilePrefix = null)
    {
        $fileName = $this->session
            ->get($this->getImportFileSessionKey($inputFilePrefix, $inputFormat));
        if (!$fileName || !file_exists($fileName)) {
            throw new BadRequestHttpException('No file to import');
        }

        return $fileName;
    }

    /**
     * Removes session variable for the given import file
     *
     * @param string $inputFilePrefix
     * @param string $inputFormat
     */
    protected function removeImportingFile($inputFilePrefix, $inputFormat)
    {
        $this->session->remove($this->getImportFileSessionKey($inputFilePrefix, $inputFormat));
    }

    /**
     * @param string $inputFilePrefix
     * @param string $inputFormat
     * @return string
     */
    protected function getImportFileSessionKey($inputFilePrefix, $inputFormat)
    {
        return sprintf('oro_importexport_import_%s_%s', $inputFilePrefix, $inputFormat);
    }

    /**
     * @param $counts
     * @return string
     */
    protected function getImportInfo($counts)
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
            ['%added%' => $add, '%updated%' => $update]
        );

        return $importInfo;
    }
}
