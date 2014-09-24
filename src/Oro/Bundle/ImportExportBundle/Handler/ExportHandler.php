<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\MimeType\MimeTypeGuesser;

class ExportHandler extends AbstractHandler
{
    /**
     * @var MimeTypeGuesser
     */
    protected $mimeTypeGuesser;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param MimeTypeGuesser $mimeTypeGuesser
     */
    public function setMimeTypeGuesser(MimeTypeGuesser $mimeTypeGuesser)
    {
        $this->mimeTypeGuesser = $mimeTypeGuesser;
    }

    /**
     * @param RouterInterface $router
     */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Get export result
     *
     * @param string $jobName
     * @param string $processorAlias
     * @param string $processorType
     * @param string $outputFormat
     * @param string $outputFilePrefix
     * @param array $options
     * @return array
     */
    public function getExportResult(
        $jobName,
        $processorAlias,
        $processorType = ProcessorRegistry::TYPE_EXPORT,
        $outputFormat = 'csv',
        $outputFilePrefix = null,
        array $options = []
    ) {
        if ($outputFilePrefix === null) {
            $outputFilePrefix = $processorAlias;
        }
        $fileName   = $this->generateExportFileName($outputFilePrefix, $outputFormat);
        $entityName = $this->processorRegistry->getProcessorEntityName(
            $processorType,
            $processorAlias
        );

        $configuration = [
            $processorType =>
                array_merge(
                    [
                        'processorAlias' => $processorAlias,
                        'entityName'     => $entityName,
                        'filePath'       => $fileName
                    ],
                    $options
                )
        ];

        $url         = null;
        $errorsCount = 0;
        $readsCount  = 0;

        $jobResult = $this->jobExecutor->executeJob(
            $processorType,
            $jobName,
            $configuration
        );

        if ($jobResult->isSuccessful()) {
            $url     = $this->router->generate(
                'oro_importexport_export_download',
                ['fileName' => basename($fileName)]
            );
            $context = $jobResult->getContext();
            if ($context) {
                $readsCount = $context->getReadCount();
            }
        } else {
            $url         = $this->router->generate(
                'oro_importexport_error_log',
                ['jobCode' => $jobResult->getJobCode()]
            );
            $errorsCount = count($jobResult->getFailureExceptions());
        }

        return [
            'success'     => $jobResult->isSuccessful(),
            'url'         => $url,
            'readsCount'  => $readsCount,
            'errorsCount' => $errorsCount,
        ];
    }

    /**
     * Handles export action
     *
     * @param string $jobName
     * @param string $processorAlias
     * @param string $exportType
     * @param string $outputFormat
     * @param string $outputFilePrefix
     * @param array $options
     * @return JsonResponse
     */
    public function handleExport(
        $jobName,
        $processorAlias,
        $exportType = 'export',
        $outputFormat = 'csv',
        $outputFilePrefix = null,
        array $options = []
    ) {
        return new JsonResponse(
            $this->getExportResult(
                $jobName,
                $processorAlias,
                $exportType,
                $outputFormat,
                $outputFilePrefix,
                $options
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
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        return $this->mimeTypeGuesser->guessByFileExtension($extension);
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
