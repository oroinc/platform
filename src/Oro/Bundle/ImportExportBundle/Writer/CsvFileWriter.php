<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

class CsvFileWriter extends CsvFileStreamWriter implements StepExecutionAwareInterface
{
    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var DoctrineClearWriter
     */
    protected $clearWriter;

    /**
     * @param ContextRegistry $contextRegistry
     */
    public function __construct(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
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

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        parent::write($items);

        if ($this->clearWriter) {
            $this->clearWriter->write($items);
        }
    }

    /**
     * @param DoctrineClearWriter $clearWriter
     */
    public function setClearWriter(DoctrineClearWriter $clearWriter)
    {
        $this->clearWriter = $clearWriter;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $context = $this->contextRegistry->getByStepExecution($stepExecution);
        $this->setImportExportContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        if (!$context->hasOption('filePath')) {
            throw new InvalidConfigurationException(
                'Configuration of CSV writer must contain "filePath".'
            );
        } else {
            $this->setFilePath($context->getOption('filePath'));
        }

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
