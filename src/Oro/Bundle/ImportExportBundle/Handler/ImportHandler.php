<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\File\FileSystemOperator;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

class ImportHandler extends AbstractHandler
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * Constructor
     *
     * @param JobExecutor        $jobExecutor
     * @param ProcessorRegistry  $processorRegistry
     * @param FileSystemOperator $fileSystemOperator
     * @param Session            $session
     * @param Router             $router
     * @param Translator         $translator
     */
    public function __construct(
        JobExecutor $jobExecutor,
        ProcessorRegistry $processorRegistry,
        FileSystemOperator $fileSystemOperator,
        Session $session,
        Router $router,
        Translator $translator
    ) {
        parent::__construct($jobExecutor, $processorRegistry, $fileSystemOperator, $router);
        $this->session    = $session;
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
        $fileName   = $this->getImportingFileName($inputFilePrefix, $inputFormat);
        $entityName = $this->processorRegistry->getProcessorEntityName(
            ProcessorRegistry::TYPE_IMPORT_VALIDATION,
            $processorAlias
        );

        $configuration = array(
            'import_validation' =>
                array_merge(
                    array(
                        'processorAlias' => $processorAlias,
                        'entityName'     => $entityName,
                        'filePath'       => $fileName
                    ),
                    $options
                )
        );

        $jobResult = $this->jobExecutor->executeJob(
            ProcessorRegistry::TYPE_IMPORT_VALIDATION,
            $jobName,
            $configuration
        );

        $context = $jobResult->getContext();

        $counts           = array();
        $counts['errors'] = count($jobResult->getFailureExceptions());
        if ($context) {
            $counts['process'] = 0;
            $counts['read']    = $context->getReadCount();
            $counts['process'] += $counts['add'] = $context->getAddCount();
            $counts['process'] += $counts['replace'] = $context->getReplaceCount();
            $counts['process'] += $counts['update'] = $context->getUpdateCount();
            $counts['process'] += $counts['delete'] = $context->getDeleteCount();
            $counts['error_entries'] = $context->getErrorEntriesCount();
            $counts['errors'] += count($context->getErrors());
        }

        $errorsUrl           = null;
        $errorsAndExceptions = array();
        if (!empty($counts['errors'])) {
            $errorsUrl           = $this->router->generate(
                'oro_importexport_error_log',
                array('jobCode' => $jobResult->getJobCode())
            );
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
            'errorsUrl'      => $errorsUrl,
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
     * @param array  $options
     * @return Response
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
        $fileName   = $this->getImportingFileName($inputFilePrefix, $inputFormat);
        $entityName = $this->processorRegistry->getProcessorEntityName(
            ProcessorRegistry::TYPE_IMPORT,
            $processorAlias
        );

        $configuration = array(
            'import' =>
                array_merge(
                    array(
                        'processorAlias' => $processorAlias,
                        'entityName'     => $entityName,
                        'filePath'       => $fileName
                    ),
                    $options
                )
        );

        $jobResult = $this->jobExecutor->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            $jobName,
            $configuration
        );

        if ($jobResult->isSuccessful()) {
            $this->removeImportingFile($inputFilePrefix, $inputFormat);
            $message = $this->translator->trans('oro.importexport.import.success');
        } else {
            $message = $this->translator->trans('oro.importexport.import.error');
        }

        $errorsUrl = null;
        if ($jobResult->getFailureExceptions()) {
            $errorsUrl = $this->router->generate(
                'oro_importexport_error_log',
                array('jobCode' => $jobResult->getJobCode())
            );
        }

        return new JsonResponse(
            array(
                'success'   => $jobResult->isSuccessful(),
                'message'   => $message,
                'errorsUrl' => $errorsUrl,
            )
        );
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
     * @param string $inputFilePrefix
     * @param string $inputFormat
     * @return string
     * @throws BadRequestHttpException
     */
    protected function getImportingFileName($inputFilePrefix, $inputFormat)
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
     * @param $inputFilePrefix
     * @param $inputFormat
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
}
