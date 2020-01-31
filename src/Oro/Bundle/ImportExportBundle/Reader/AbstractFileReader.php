<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;

/**
 * Provide common functional for file readers
 */
abstract class AbstractFileReader extends AbstractReader implements ClosableInterface
{
    /**
     * @var \SplFileInfo
     */
    protected $fileInfo;

    /**
     * @var \SplFileObject
     */
    protected $file;

    /**
     * @var array
     */
    protected $header;

    /**
     * @var bool
     */
    protected $firstLineIsHeader = true;

    /**
     * @var ImportStrategyHelper
     */
    protected $importHelper;

    /**
     * @param ImportStrategyHelper $importHelper
     * @return AbstractFileReader
     */
    public function setImportHelper(ImportStrategyHelper $importHelper): self
    {
        $this->importHelper = $importHelper;
        return $this;
    }

    /**
     * @param string $filePath
     * @throws InvalidArgumentException
     */
    protected function setFilePath($filePath)
    {
        $this->fileInfo = new \SplFileInfo($filePath);

        if (!$this->fileInfo->isFile()) {
            throw new InvalidArgumentException(sprintf('File "%s" does not exists.', $filePath));
        } elseif (!$this->fileInfo->isReadable()) {
            throw new InvalidArgumentException(sprintf('File "%s" is not readable.', $this->fileInfo->getRealPath()));
        }
    }

    /**
     * @param ContextInterface $context
     * @throws InvalidConfigurationException
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        if (! $context->hasOption('filePath')) {
            throw new InvalidConfigurationException(
                'Configuration of reader must contain "filePath".'
            );
        } else {
            $this->setFilePath($context->getOption('filePath'));
        }
    }

    /**
     * @return \SplFileObject
     */
    abstract protected function getFile();

    /**
     * @return array|null
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->file = null;
        $this->header = null;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateHeader()
    {
        $columnNameWithFrequency = \array_count_values($this->header);
        $nonUniqueColumns = [];
        foreach ($columnNameWithFrequency as $columnName => $frequency) {
            if ($frequency > 1) {
                $nonUniqueColumns[] = $columnName;
            }
        }
        if ($nonUniqueColumns) {
            throw new InvalidArgumentException(
                sprintf(
                    "Imported file contains duplicate in next column names: '%s'.",
                    implode('", "', $nonUniqueColumns)
                )
            );
        }
    }
    /**
     * @param array $data
     *
     * @throws InvalidItemException
     */
    protected function validateColumnCount(array $data)
    {
        if (count($this->header) !== count($data)) {
            $errorMessage = sprintf(
                'Expecting to get %d columns, actually got %d.
                        Header contains: %s 
                        Row contains: %s',
                count($this->header),
                count($data),
                print_r($this->header, true),
                print_r($data, true)
            );

            /**
             * `stepExecution` will be null in case when fileReader uses in scope of splitImportFile process
             */
            if ($this->stepExecution) {
                $importContext = $this->contextRegistry->getByStepExecution($this->stepExecution);
                $importContext->incrementErrorEntriesCount();
                $this->importHelper->addValidationErrors([$errorMessage], $importContext);
            }

            throw new InvalidItemException($errorMessage, $data);
        }
    }
}
