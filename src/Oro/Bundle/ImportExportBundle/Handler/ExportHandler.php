<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\File\FileManager;
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
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @param FileManager $fileManager
     */
    public function setFileManager(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

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
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
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
        $fileName = FileManager::generateFileName($outputFilePrefix, $outputFormat);
        $filePath = FileManager::generateTmpFilePath($fileName);
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
                        'filePath'       => $filePath
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

        $url = $this->configManager->get('oro_ui.application_url');
        $this->fileManager->writeFileToStorage($filePath, $fileName);
        unlink($filePath);
        if ($jobResult->isSuccessful()) {
            $url .= $this->router->generate('oro_importexport_export_download', ['fileName' => basename($fileName)]);
            $context = $jobResult->getContext();
            if ($context) {
                $readsCount = $context->getReadCount();
            }
        } else {
            $url .= $this->router->generate('oro_importexport_error_log', ['jobCode' => $jobResult->getJobCode()]);
            $errorsCount = count($jobResult->getFailureExceptions());
        }

        return [
            'success' => $jobResult->isSuccessful(),
            'url' => $url,
            'readsCount' => $readsCount,
            'errorsCount' => $errorsCount,
            'entities' => $this->getEntityPluralName($entityName)
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
        $exportType = ProcessorRegistry::TYPE_EXPORT,
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
        $content = $this->fileManager->getContent($fileName);
        $headers     = [];
        $contentType = $this->getFileContentType($fileName);
        if ($contentType !== null) {
            $headers['Content-Type'] = $contentType;
        }

        $response = new Response($content, 200, $headers);

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
}
