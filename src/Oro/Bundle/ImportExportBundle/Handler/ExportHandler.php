<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\File\FileSystemOperator;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\MimeType\MimeTypeGuesser;

class ExportHandler extends AbstractHandler
{
    /**
     * @var MimeTypeGuesser
     */
    protected $mimeTypeGuesser;

    /**
     * Constructor
     *
     * @param JobExecutor        $jobExecutor
     * @param ProcessorRegistry  $processorRegistry
     * @param FileSystemOperator $fileSystemOperator
     * @param MimeTypeGuesser    $mimeTypeGuesser
     * @param Router             $router
     */
    public function __construct(
        JobExecutor $jobExecutor,
        ProcessorRegistry $processorRegistry,
        FileSystemOperator $fileSystemOperator,
        MimeTypeGuesser $mimeTypeGuesser,
        Router $router
    ) {
        parent::__construct($jobExecutor, $processorRegistry, $fileSystemOperator, $router);
        $this->mimeTypeGuesser = $mimeTypeGuesser;
    }

    /**
     * Handles export action
     *
     * @param string $jobName
     * @param string $processorAlias
     * @param string $outputFormat
     * @param string $outputFilePrefix
     * @param array  $options
     * @return Response
     */
    public function handleExport(
        $jobName,
        $processorAlias,
        $outputFormat = 'csv',
        $outputFilePrefix = null,
        array $options = []
    ) {
        if ($outputFilePrefix === null) {
            $outputFilePrefix = $processorAlias;
        }
        $fileName   = $this->generateExportFileName($outputFilePrefix, $outputFormat);
        $entityName = $this->processorRegistry->getProcessorEntityName(
            ProcessorRegistry::TYPE_EXPORT,
            $processorAlias
        );

        $configuration = array(
            'export' =>
                array_merge(
                    array(
                        'processorAlias' => $processorAlias,
                        'entityName'     => $entityName,
                        'filePath'       => $fileName
                    ),
                    $options
                )
        );

        $url         = null;
        $errorsCount = 0;
        $readsCount  = 0;

        $jobResult = $this->jobExecutor->executeJob(
            ProcessorRegistry::TYPE_EXPORT,
            $jobName,
            $configuration
        );

        if ($jobResult->isSuccessful()) {
            $url     = $this->router->generate(
                'oro_importexport_export_download',
                array('fileName' => basename($fileName))
            );
            $context = $jobResult->getContext();
            if ($context) {
                $readsCount = $context->getReadCount();
            }
        } else {
            $url         = $this->router->generate(
                'oro_importexport_error_log',
                array('jobCode' => $jobResult->getJobCode())
            );
            $errorsCount = count($jobResult->getFailureExceptions());
        }

        return new JsonResponse(
            array(
                'success'     => $jobResult->isSuccessful(),
                'url'         => $url,
                'readsCount'  => $readsCount,
                'errorsCount' => $errorsCount,
            )
        );
    }

    /**
     * Handles download export file action
     *
     * @param $fileName
     * @return Response
     */
    public function handleDownloadExportResult($fileName)
    {
        $fullFileName = $this->fileSystemOperator
            ->getTemporaryFile($fileName)
            ->getRealPath();

        $headers     = [];
        $contentType = $this->getFileContentType($fullFileName);
        if ($contentType !== null) {
            $headers['Content-Type'] = $contentType;
        }

        $response = new BinaryFileResponse($fullFileName, 200, $headers);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $response;
    }

    /**
     * Tries to guess MIME type of the given file
     *
     * @param string $fileName
     * @return string|null
     */
    protected function getFileContentType($fileName)
    {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);

        return $this->mimeTypeGuesser->guessByFileExtension($ext);
    }

    /**
     * Get a mimetype value from a file extension
     *
     * @param string $extension File extension
     *
     * @return string|null
     *
     */
    protected function guessMimetypeByFileExtension($extension)
    {
        $extension = strtolower($extension);

        return isset($this->mimetypes[$extension]) ? $this->mimetypes[$extension] : null;
    }

    /**
     * Builds a name of exported file
     *
     * @param string $prefix
     * @param string $extension
     * @return string
     */
    protected function generateExportFileName($prefix, $extension)
    {
        return $this->fileSystemOperator
            ->generateTemporaryFileName($prefix, $extension);
    }
}
