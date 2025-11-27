<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Gaufrette\Exception\FileNotFound;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\ImportExportBundle\Exception\FileSizeExceededException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Reader\AbstractFileReader;
use Oro\Bundle\ImportExportBundle\Reader\BatchIdsReaderInterface;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Component\PhpUtils\PhpIniUtil;
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
        $entityName = $this->processorRegistry->getProcessorEntityName($processorType, $processorAlias);
        $fileName = FileManager::generateFileName($outputFilePrefix, $outputFormat);
        $tmpFilePath = $this->fileManager->createTmpFile();

        $configuration = [
            $processorType => array_merge(
                [
                    'processorAlias' => $processorAlias,
                    'entityName' => $entityName,
                    'filePath' => $tmpFilePath
                ],
                $options
            )
        ];

        $jobResult = $this->jobExecutor->executeJob($processorType, $jobName, $configuration);

        try {
            $this->fileManager->writeFileToStorage($tmpFilePath, $fileName);
        } catch (\Exception $exception) {
            $jobResult->addFailureException($exception);
        } finally {
            $this->fileManager->deleteTmpFile($tmpFilePath);
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
        $this->batchFileManager->setWriter($this->getWriter($outputFormat));
        $this->batchFileManager->setReader($this->getReader($outputFormat));

        $fileName = FileManager::generateFileName($processorType, $outputFormat);
        $summaryTmpFilePath = $this->fileManager->createTmpFile();
        $tmpFilePaths = [];
        try {
            $memoryLimit = (int)PhpIniUtil::parseBytes(ini_get('memory_limit'));
            $filesSize = $this->getFilesSize($files);
            $this->checkDiskSpace($files, $summaryTmpFilePath);

            foreach ($files as $file) {
                $this->checkMemoryLimit($filesSize, $memoryLimit);

                try {
                    $tmpFilePath = $this->fileManager->writeToTmpLocalStorage($file);
                } catch (FileNotFound $e) {
                    continue;
                }
                if ('csv' === $outputFormat) {
                    $tmpFilePath = $this->fileManager->fixNewLines($tmpFilePath);
                }

                $tmpFilePaths[] = $tmpFilePath;
            }
            $this->batchFileManager->mergeFiles($tmpFilePaths, $summaryTmpFilePath);

            $this->fileManager->copyFileToStorage($summaryTmpFilePath, $fileName);

            foreach ($files as $file) {
                $this->fileManager->deleteFile($file);
            }
        } catch (FileSizeExceededException $exception) {
            $this->removeUnusedChunks($files);
            throw $exception;
        } catch (\Exception $exception) {
            throw new RuntimeException('Cannot merge export files into single summary file', 0, $exception);
        } finally {
            $this->fileManager->deleteTmpFile($summaryTmpFilePath);
            foreach ($tmpFilePaths as $tmpFilePath) {
                $this->fileManager->deleteTmpFile($tmpFilePath);
            }
        }

        return $fileName;
    }

    private function getReader($outputFormat): AbstractFileReader
    {
        $reader = $this->readerChain->getReader($outputFormat);
        if (!$reader instanceof AbstractFileReader) {
            throw new LogicException('Reader must be instance of AbstractFileReader');
        }

        return $reader;
    }

    private function getWriter($outputFormat): FileStreamWriter
    {
        $writer = $this->writerChain->getWriter($outputFormat);
        if (!$writer instanceof FileStreamWriter) {
            throw new LogicException('Writer must be instance of FileStreamWriter');
        }

        return $writer;
    }

    private function checkMemoryLimit(int $filesSize, $memoryLimit): void
    {
        if ($memoryLimit > 0 && $filesSize > ($memoryLimit - memory_get_usage(true))) {
            throw new FileSizeExceededException("Total size of import files exceeds current memory limit!");
        }
    }

    private function checkDiskSpace(array $files, string $localFilePath): void
    {
        $folderSizeLimit = (int)disk_free_space(dirname($localFilePath));

        $filesSize = $this->getFilesSize($files);

        if ($filesSize > $folderSizeLimit) {
            throw new FileSizeExceededException("Total size of import files exceeds current disk space!");
        }
    }

    private function getFilesSize(array $files): int
    {
        return array_sum(array_map([$this->fileManager, 'getFileSize'], $files));
    }

    private function removeUnusedChunks($files): void
    {
        foreach ($files as $file) {
            $fullPath = $this->fileManager->getFilePath($file);
            @unlink($fullPath);
        }
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
