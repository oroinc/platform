<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;

/**
 * Write xlsx to file
 */
class XlsxFileWriter extends XlsxFileStreamWriter implements StepExecutionAwareInterface
{
    private ContextRegistry $contextRegistry;
    private DoctrineClearWriter $clearWriter;

    public function __construct(ContextRegistry $contextRegistry, DoctrineClearWriter $clearWriter)
    {
        $this->contextRegistry = $contextRegistry;
        $this->clearWriter = $clearWriter;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items): void
    {
        parent::write($items);

        $this->clearWriter->write($items);
    }
    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution): void
    {
        $this->clearWriter->setStepExecution($stepExecution);
        $context = $this->contextRegistry->getByStepExecution($stepExecution);
        $this->setImportExportContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context): void
    {
        if (!$context->hasOption('filePath')) {
            throw new InvalidConfigurationException(
                'Configuration of XLSX writer must contain "filePath".'
            );
        }

        $this->setFilePath($context->getOption('filePath'));

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
