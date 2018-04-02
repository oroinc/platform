<?php

namespace Oro\Bundle\DataGridBundle\Handler;

use Akeneo\Bundle\BatchBundle\Item\ItemReaderInterface;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionWarningHandlerInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutor;
use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportIdFetcher;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Psr\Log\LoggerInterface;

class ExportHandler implements StepExecutionWarningHandlerInterface
{
    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $exportFailed = false;

    /**
     * @param FileManager $fileManager
     */
    public function setFileManager(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ItemReaderInterface $reader
     * @param ExportProcessor $processor
     * @param ItemWriterInterface $writer
     * @param array $contextParameters
     * @param int $batchSize
     * @param string $format
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function handle(
        ItemReaderInterface $reader,
        ExportProcessor $processor,
        ItemWriterInterface $writer,
        array $contextParameters,
        $batchSize,
        $format
    ) {
        if (!isset($contextParameters['gridName'])) {
            throw new InvalidArgumentException('Parameter "gridName" must be provided.');
        }

        $fileName = FileManager::generateFileName(sprintf('datagrid_%s', $contextParameters['gridName']), $format);
        $filePath = FileManager::generateTmpFilePath($fileName);

        $contextParameters['filePath'] = $filePath;

        $context = new Context($contextParameters);
        $executor = new StepExecutor();
        $executor->setBatchSize($batchSize);
        $executor
            ->setReader($reader)
            ->setProcessor($processor)
            ->setWriter($writer);
        foreach ([$executor->getReader(), $executor->getProcessor(), $executor->getWriter()] as $element) {
            if ($element instanceof ContextAwareInterface) {
                $element->setImportExportContext($context);
            }
        }

        try {
            $executor->execute($this);
            $this->fileManager->writeFileToStorage($filePath, $fileName);
        } catch (\Exception $exception) {
            $context->addFailureException($exception);
        } finally {
            @unlink($filePath);
        }

        $readsCount = $context->getReadCount() ?: 0;
        $errorsCount = count($context->getFailureExceptions());
        $errors = array_slice(array_merge($context->getErrors(), $context->getFailureExceptions()), 0, 100);
        if ($writer instanceof ClosableInterface) {
            $writer->close();
        }
        if ($reader instanceof ClosableInterface) {
            $reader->close();
        }

        return [
            'success' => !$this->exportFailed,
            'file' => $fileName,
            'readsCount' => $readsCount,
            'errorsCount' => $errorsCount,
            'errors' => $errors
         ];
    }

    /**
     * @param object $element
     * @param string $name
     * @param string $reason
     * @param array $reasonParameters
     * @param mixed $item
     */
    public function handleWarning($element, $name, $reason, array $reasonParameters, $item)
    {
        $this->exportFailed = true;

        $this->logger->error(sprintf('[DataGridExportHandle] Error message: %s', $reason), ['element' => $element]);
    }

    /**
     * @param DatagridExportIdFetcher $idFetcher
     * @param array $parameters
     *
     * @return array
     */
    public function getExportingEntityIds(DatagridExportIdFetcher $idFetcher, array $parameters)
    {
        $context  = new Context($parameters);
        $idFetcher->setImportExportContext($context);

        return $idFetcher->getGridDataIds();
    }
}
