<?php

namespace Oro\Bundle\DataGridBundle\Handler;

use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionWarningHandlerInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutor;
use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Psr\Log\LoggerInterface;

/**
 * Combines the basic logic of exports.
 * Responsible for combining the components of reader, processor and writer of export data.
 * Processes and creates statistics on the export result.
 */
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

    public function setFileManager(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

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
        $tmpFilePath = $this->fileManager->createTmpFile();

        $contextParameters[Context::OPTION_FILE_PATH] = $tmpFilePath;

        $context = new Context($contextParameters);
        $executor = new StepExecutor();
        $executor->setBatchSize($batchSize);
        $executor->setReader($reader);
        $executor->setProcessor($processor);
        $executor->setWriter($writer);
        $processor->setImportExportContext($context);
        if ($reader instanceof ContextAwareInterface) {
            $reader->setImportExportContext($context);
        }
        if ($writer instanceof ContextAwareInterface) {
            $writer->setImportExportContext($context);
        }

        try {
            $executor->execute($this);
            $this->fileManager->writeFileToStorage($tmpFilePath, $fileName);
        } catch (\Exception $exception) {
            $context->addFailureException($exception);
        } finally {
            $this->fileManager->deleteTmpFile($tmpFilePath);
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
    #[\Override]
    public function handleWarning($element, $name, $reason, array $reasonParameters, $item)
    {
        $this->exportFailed = true;

        $this->logger->error(sprintf('[DataGridExportHandle] Error message: %s', $reason), ['element' => $element]);
    }
}
