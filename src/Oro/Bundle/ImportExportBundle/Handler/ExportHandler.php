<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\MimeType\MimeTypeGuesser;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Reader\AbstractFileReader;
use Oro\Bundle\ImportExportBundle\Reader\BatchIdsReaderInterface;
use Oro\Bundle\ImportExportBundle\Reader\ReaderChain;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;

class ExportHandler extends AbstractHandler
{
    /**
     * @var MimeTypeGuesser
     */
    protected $mimeTypeGuesser;

    /**
     * @var ReaderChain
     */
    protected $readerChain;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var WriterChain
     */
    protected $writerChain;
    
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
     * @param ReaderChain $readerChain
     */
    public function setReaderChain(ReaderChain $readerChain)
    {
        $this->readerChain = $readerChain;
    }

    /**
     * @param WriterChain $writerChain
     */
    public function setWriterChain(WriterChain $writerChain)
    {
        $this->writerChain = $writerChain;
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
            $outputFilePrefix = $processorType;
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
                        'entityName' => $entityName,
                        'filePath' => $filePath
                    ],
                    $options
                )
        ];
        $errorsCount = 0;
        $readsCount = 0;
        $errors = [];

        $jobResult = $this->jobExecutor->executeJob(
            $processorType,
            $jobName,
            $configuration
        );

        if ($context = $jobResult->getContext()) {
            $errors = $context->getErrors();
        }
        if ($jobResult->getFailureExceptions()) {
            $errors = array_merge($errors, $jobResult->getFailureExceptions());
        }

        $this->fileManager->writeFileToStorage($filePath, $fileName);
        @unlink($filePath);

        if ($jobResult->isSuccessful() && ($context = $jobResult->getContext())) {
            $readsCount = $context->getReadCount();
        } else {
            $errorsCount = count($jobResult->getFailureExceptions());
        }
        
        $errors = array_slice($errors, 0, 100);
        
        if (($writer = $this->writerChain->getWriter($outputFormat)) && $writer instanceof ClosableInterface) {
            $writer->close();
        }

        return [
            'success' => $jobResult->isSuccessful(),
            'file' => $fileName,
            'readsCount' => $readsCount,
            'errorsCount' => $errorsCount,
            'entities' => $this->getEntityPluralName($entityName),
            'errors' => $errors
        ];
    }

    /**
     * Method for getting ids for export
     *
     * @param string $jobName
     * @param string $processorType
     * @param string $processorAlias
     * @param string $options
     * @return array
     */
    public function getExportingEntityIds($jobName, $processorType, $processorAlias, $options)
    {
        if (! ($reader = $this->getJobReader($jobName, $processorType)) instanceof BatchIdsReaderInterface) {
            return [];
        }

        $entityName = $this->processorRegistry->getProcessorEntityName(
            $processorType,
            $processorAlias
        );
        /** @var BatchIdsReaderInterface $reader */

        return $reader->getIds($entityName, $options);
    }

    /**
     * @param string $jobName
     * @param string $processorType
     * @param string $outputFormat
     * @param array $files
     * @return string
     */
    public function exportResultFileMerge($jobName, $processorType, $outputFormat, array $files)
    {
        $fileName = FileManager::generateFileName($processorType, $outputFormat);
        $localFilePath = FileManager::generateTmpFilePath($fileName);

        if (! ($writer = $this->writerChain->getWriter($outputFormat)) instanceof FileStreamWriter) {
            throw new LogicException('Writer must be instance of FileStreamWriter');
        }
        if (! ($reader = $this->readerChain->getReader($outputFormat)) instanceof AbstractFileReader) {
            throw new LogicException('Reader must be instance of AbstractFileReader');
        }
        $this->batchFileManager->setWriter($writer);
        $this->batchFileManager->setReader($reader);

        $localFiles = [];

        foreach ($files as $file) {
            $localFiles[] = $this->fileManager->writeToTmpLocalStorage($file);
        }
        $this->batchFileManager->mergeFiles($localFiles, $localFilePath);

        $this->fileManager->writeFileToStorage($localFilePath, $fileName, true);

        foreach ($files as $file) {
            $this->fileManager->deleteFile($file);
        }
        foreach ($localFiles as $localFile) {
            @unlink($localFile);
        }
        @unlink($localFilePath);

        return $fileName;
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
     * @param string $fileName
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
