<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;

/**
 * Batch job CSV file writer.
 */
class CsvFileWriter extends CsvFileStreamWriter implements StepExecutionAwareInterface
{
    private ContextRegistry $contextRegistry;
    private DoctrineClearWriter $clearWriter;

    public function __construct(ContextRegistry $contextRegistry, DoctrineClearWriter $clearWriter)
    {
        parent::__construct();
        $this->contextRegistry = $contextRegistry;
        $this->clearWriter = $clearWriter;
    }

    /**
     * Open file.
     *
     * @return resource
     */
    protected function open()
    {
        return fopen($this->filePath, 'a');
    }

    public function write(array $items)
    {
        parent::write($items);

        $this->clearWriter->write($items);
    }

    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->clearWriter->setStepExecution($stepExecution);
        $context = $this->contextRegistry->getByStepExecution($stepExecution);
        $this->setImportExportContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        if (!$context->hasOption(Context::OPTION_FILE_PATH)) {
            throw new InvalidConfigurationException(
                'Configuration of CSV writer must contain "filePath".'
            );
        }

        $this->setFilePath($context->getOption(Context::OPTION_FILE_PATH));

        parent::setImportExportContext($context);
    }

    /**
     * @param string $filePath
     * @throws InvalidArgumentException
     */
    protected function setFilePath($filePath)
    {
        $dirPath = dirname($filePath);
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException(sprintf('Directory "%s" does not exists.', $dirPath));
        } elseif (!is_writable($dirPath)) {
            throw new InvalidArgumentException(sprintf('Directory "%s" is not writable.', realpath($dirPath)));
        }

        $this->filePath = $filePath;
    }
}
