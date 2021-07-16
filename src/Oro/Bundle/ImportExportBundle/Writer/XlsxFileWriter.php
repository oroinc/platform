<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;

/**
 * Write xlsx to file
 */
class XlsxFileWriter extends XlsxFileStreamWriter implements StepExecutionAwareInterface
{
    /** @var ContextRegistry */
    protected $contextRegistry;

    /** @var DoctrineClearWriter */
    protected $clearWriter;

    public function __construct(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items): void
    {
        parent::write($items);

        if ($this->clearWriter) {
            $this->clearWriter->write($items);
        }
    }

    public function setClearWriter(DoctrineClearWriter $clearWriter): void
    {
        $this->clearWriter = $clearWriter;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution): void
    {
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
