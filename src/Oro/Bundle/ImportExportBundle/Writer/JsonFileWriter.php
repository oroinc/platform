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
 * Writes JSON to a file.
 */
class JsonFileWriter extends JsonFileStreamWriter implements StepExecutionAwareInterface
{
    public function __construct(
        private ContextRegistry $contextRegistry,
        private DoctrineClearWriter $clearWriter
    ) {
    }

    #[\Override]
    protected function open()
    {
        return fopen($this->filePath, 'a');
    }

    #[\Override]
    public function write(array $items): void
    {
        parent::write($items);

        $this->clearWriter->write($items);
    }

    #[\Override]
    public function setStepExecution(StepExecution $stepExecution): void
    {
        $this->clearWriter->setStepExecution($stepExecution);
        $context = $this->contextRegistry->getByStepExecution($stepExecution);
        $this->setImportExportContext($context);
    }

    #[\Override]
    public function setImportExportContext(ContextInterface $context): void
    {
        if (!$context->hasOption(Context::OPTION_FILE_PATH)) {
            throw new InvalidConfigurationException(
                'Configuration of JSON writer must contain "filePath".'
            );
        }

        $this->setFilePath($context->getOption(Context::OPTION_FILE_PATH));

        parent::setImportExportContext($context);
    }

    private function setFilePath(string $filePath): void
    {
        $dirPath = \dirname($filePath);
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException(\sprintf('Directory "%s" does not exists.', $dirPath));
        }
        if (!is_writable($dirPath)) {
            throw new InvalidArgumentException(\sprintf('Directory "%s" is not writable.', realpath($dirPath)));
        }

        $this->filePath = $filePath;
    }
}
