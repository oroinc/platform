<?php

namespace Oro\Bundle\ImportExportBundle\Context;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\BatchBundle\Entity\StepExecution;

/**
 * Provides the ability to save and manage parameters
 * Performs the role of an adapter and provides access to the original object
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StepExecutionProxyContext implements ContextInterface, BatchContextInterface
{
    /**
     * @var StepExecution
     */
    protected $stepExecution;

    /**
     * @var array
     */
    private $postponedRows = [];

    public function __construct(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    #[\Override]
    public function addError($message)
    {
        $this->stepExecution->addError($message);
    }

    #[\Override]
    public function addErrors(array $messages)
    {
        foreach ($messages as $message) {
            $this->addError($message);
        }
    }

    #[\Override]
    public function getErrors()
    {
        return $this->stepExecution->getErrors();
    }

    #[\Override]
    public function addPostponedRow(array $row)
    {
        $this->postponedRows[] = $row;

        return $this;
    }

    #[\Override]
    public function addPostponedRows(array $rows)
    {
        foreach ($rows as $row) {
            $this->addPostponedRow($row);
        }

        return $this;
    }

    #[\Override]
    public function getPostponedRows()
    {
        return $this->postponedRows;
    }

    /**
     * @return ArrayCollection
     */
    public function getWarnings()
    {
        return $this->stepExecution->getWarnings();
    }

    #[\Override]
    public function getFailureExceptions()
    {
        return array_map(
            function ($e) {
                return $e['message'];
            },
            $this->stepExecution->getFailureExceptions()
        );
    }

    #[\Override]
    public function incrementReadCount($incrementBy = 1)
    {
        $incrementedRead = $this->getOption('incremented_read', true);
        if ($incrementedRead) {
            $this->stepExecution->setReadCount(
                $this->stepExecution->getReadCount() + $incrementBy
            );
        }
    }

    #[\Override]
    public function getReadCount()
    {
        return $this->stepExecution->getReadCount();
    }

    #[\Override]
    public function incrementReadOffset()
    {
        $this->setValue('read_offset', (int)$this->getValue('read_offset') + 1);
    }

    #[\Override]
    public function getReadOffset()
    {
        return $this->getValue('read_offset');
    }

    #[\Override]
    public function incrementAddCount($incrementBy = 1)
    {
        $this->setValue('add_count', (int)$this->getValue('add_count') + $incrementBy);
    }

    #[\Override]
    public function getAddCount()
    {
        return $this->getValue('add_count');
    }

    #[\Override]
    public function incrementUpdateCount($incrementBy = 1)
    {
        $this->setValue('update_count', (int)$this->getValue('update_count') + $incrementBy);
    }

    #[\Override]
    public function getUpdateCount()
    {
        return $this->getValue('update_count');
    }

    #[\Override]
    public function incrementReplaceCount($incrementBy = 1)
    {
        $this->setValue('replace_count', (int)$this->getValue('replace_count') + $incrementBy);
    }

    #[\Override]
    public function getReplaceCount()
    {
        return $this->getValue('replace_count');
    }

    #[\Override]
    public function incrementDeleteCount($incrementBy = 1)
    {
        $this->setValue('delete_count', (int)$this->getValue('delete_count') + $incrementBy);
    }

    #[\Override]
    public function getDeleteCount()
    {
        return $this->getValue('delete_count');
    }

    #[\Override]
    public function incrementErrorEntriesCount($incrementBy = 1)
    {
        $this->setValue('error_entries_count', (int)$this->getValue('error_entries_count') + $incrementBy);
    }

    #[\Override]
    public function getErrorEntriesCount()
    {
        return $this->getValue('error_entries_count');
    }

    #[\Override]
    public function setValue($name, $value)
    {
        $this->stepExecution->getExecutionContext()->put($name, $value);
    }

    #[\Override]
    public function getValue($name)
    {
        return $this->stepExecution->getExecutionContext()->get($name);
    }

    #[\Override]
    public function getConfiguration()
    {
        $stepName = $this->stepExecution->getStepName();
        $jobInstance = $this->stepExecution->getJobExecution()->getJobInstance();
        $rawConfiguration = $jobInstance ? $jobInstance->getRawConfiguration() : [];

        return !empty($rawConfiguration[$stepName]) ? $rawConfiguration[$stepName] : $rawConfiguration;
    }

    #[\Override]
    public function hasOption($name)
    {
        $configuration = $this->getConfiguration();

        return isset($configuration[$name]);
    }

    #[\Override]
    public function getOption($name, $default = null)
    {
        $configuration = $this->getConfiguration();
        if (isset($configuration[$name])) {
            return $configuration[$name];
        }

        return $default;
    }

    #[\Override]
    public function removeOption($name)
    {
        $configuration = $this->getConfiguration();
        if (isset($configuration[$name])) {
            unset($configuration[$name]);
            $this->stepExecution->getJobExecution()->getJobInstance()->setRawConfiguration($configuration);
        }
    }

    #[\Override]
    public function getBatchSize()
    {
        return $this->getOption('batch_size');
    }

    #[\Override]
    public function getBatchNumber()
    {
        return $this->getOption('batch_number');
    }
}
