<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Gaufrette\Exception\FileNotFound;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Reader\AbstractFileReader;
use Oro\Bundle\ImportExportBundle\Reader\BatchIdsReaderInterface;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles export logic such as getting export result, merging exported files, etc.
 */
class ExportHandler extends AbstractHandler
{
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
        if (null === $outputFilePrefix) {
            $outputFilePrefix = $processorType;
        }
        $fileName = FileManager::generateFileName($outputFilePrefix, $outputFormat);
        $filePath = FileManager::generateTmpFilePath($fileName);
        $entityName = $this->processorRegistry->getProcessorEntityName($processorType, $processorAlias);

        $configuration = [
            $processorType => array_merge(
                [
                    'processorAlias' => $processorAlias,
                    'entityName' => $entityName,
                    'filePath' => $filePath
                ],
                $options
            )
        ];

        $jobResult = $this->jobExecutor->executeJob($processorType, $jobName, $configuration);

        try {
            $this->fileManager->writeFileToStorage($filePath, $fileName);
        } catch (\Exception $exception) {
            $jobResult->addFailureException($exception);
        } finally {
            @unlink($filePath);
        }

        $errors = [];
        $readsCount = 0;
        $context = $jobResult->getContext();
        if ($context) {
            $errors = $context->getErrors();

            if ($jobResult->isSuccessful()) {
                $readsCount = $context->getReadCount();
            }
        }

        $errorsCount = count($jobResult->getFailureExceptions());
        if ($errorsCount > 0) {
            $errors = array_merge($errors, $jobResult->getFailureExceptions());
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
     * @param array $options
     * @return array
     */
    public function getExportingEntityIds($jobName, $processorType, $processorAlias, $options)
    {
        $reader = $this->getJobReader($jobName, $processorType);
        if (!$reader instanceof BatchIdsReaderInterface) {
            return [];
        }

        return $reader->getIds(
            $this->processorRegistry->getProcessorEntityName($processorType, $processorAlias),
            $options
        );
    }

    /**
     * @param string $jobName
     * @param string $processorType
     * @param string $outputFormat
     * @param array $files
     *
     * @return string
     *
     * @throws RuntimeException
     */
    public function exportResultFileMerge($jobName, $processorType, $outputFormat, array $files)
    {
        $fileName = FileManager::generateFileName($processorType, $outputFormat);
        $localFilePath = FileManager::generateTmpFilePath($fileName);

        $writer = $this->writerChain->getWriter($outputFormat);
        if (!$writer instanceof FileStreamWriter) {
            throw new LogicException('Writer must be instance of FileStreamWriter');
        }

        $reader = $this->readerChain->getReader($outputFormat);
        if (!$reader instanceof AbstractFileReader) {
            throw new LogicException('Reader must be instance of AbstractFileReader');
        }

        $this->batchFileManager->setWriter($writer);
        $this->batchFileManager->setReader($reader);

        $localFiles = [];

        try {
            foreach ($files as $file) {
                try {
                    $tmpPath = $this->fileManager->writeToTmpLocalStorage($file);
                } catch (FileNotFound $e) {
                    continue;
                }
                if ($outputFormat === 'csv') {
                    $tmpPath = $this->fileManager->fixNewLines($tmpPath);
                }

                $localFiles[] = $tmpPath;
            }
            $this->batchFileManager->mergeFiles($localFiles, $localFilePath);

            $this->fileManager->writeFileToStorage($localFilePath, $fileName);

            foreach ($files as $file) {
                $this->fileManager->deleteFile($file);
            }
        } catch (\Exception $exception) {
            throw new RuntimeException('Cannot merge export files into single summary file', 0, $exception);
        } finally {
            foreach ($localFiles as $localFile) {
                @unlink($localFile);
            }
            @unlink($localFilePath);
        }

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
        if (!$this->fileManager->isFileExist($fileName)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($this->fileManager->getFilePath($fileName));
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName)
        );

        $contentType = $this->fileManager->getMimeType($fileName);
        if (null !== $contentType) {
            $response->headers->set('Content-Type', $contentType);
        }

        return $response;
    }
}
