<?php

namespace Oro\Bundle\ImportExportBundle\Context;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Provides the ability to save and manage parameters
 * Performs the role of an adapter and provides access to the original object
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

    /**
     * {@inheritdoc}
     */
    public function addError($message)
    {
        $this->stepExecution->addError($message);
    }

    /**
     * {@inheritdoc}
     */
    public function addErrors(array $messages)
    {
        foreach ($messages as $message) {
            $this->addError($message);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->stepExecution->getErrors();
    }

    /**
     * {@inheritdoc}
     */
    public function addPostponedRow(array $row)
    {
        $this->postponedRows[] = $row;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addPostponedRows(array $rows)
    {
        foreach ($rows as $row) {
            $this->addPostponedRow($row);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getFailureExceptions()
    {
        return array_map(
            function ($e) {
                return $e['message'];
            },
            $this->stepExecution->getFailureExceptions()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function incrementReadCount($incrementBy = 1)
    {
        $incrementedRead = $this->getOption('incremented_read', true);
        if ($incrementedRead) {
            $this->stepExecution->setReadCount(
                $this->stepExecution->getReadCount() + $incrementBy
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getReadCount()
    {
        return $this->stepExecution->getReadCount();
    }

    /**
     * {@inheritdoc}
     */
    public function incrementReadOffset()
    {
        $this->setValue('read_offset', (int)$this->getValue('read_offset') + 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getReadOffset()
    {
        return $this->getValue('read_offset');
    }

    /**
     * {@inheritdoc}
     */
    public function incrementAddCount($incrementBy = 1)
    {
        $this->setValue('add_count', (int)$this->getValue('add_count') + $incrementBy);
    }

    /**
     * {@inheritdoc}
     */
    public function getAddCount()
    {
        return $this->getValue('add_count');
    }

    /**
     * {@inheritdoc}
     */
    public function incrementUpdateCount($incrementBy = 1)
    {
        $this->setValue('update_count', (int)$this->getValue('update_count') + $incrementBy);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateCount()
    {
        return $this->getValue('update_count');
    }

    /**
     * {@inheritdoc}
     */
    public function incrementReplaceCount($incrementBy = 1)
    {
        $this->setValue('replace_count', (int)$this->getValue('replace_count') + $incrementBy);
    }

    /**
     * {@inheritdoc}
     */
    public function getReplaceCount()
    {
        return $this->getValue('replace_count');
    }

    /**
     * {@inheritdoc}
     */
    public function incrementDeleteCount($incrementBy = 1)
    {
        $this->setValue('delete_count', (int)$this->getValue('delete_count') + $incrementBy);
    }

    /**
     * {@inheritdoc}
     */
    public function getDeleteCount()
    {
        return $this->getValue('delete_count');
    }

    /**
     * {@inheritdoc}
     */
    public function incrementErrorEntriesCount($incrementBy = 1)
    {
        $this->setValue('error_entries_count', (int)$this->getValue('error_entries_count') + $incrementBy);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorEntriesCount()
    {
        return $this->getValue('error_entries_count');
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($name, $value)
    {
        $this->stepExecution->getExecutionContext()->put($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($name)
    {
        return $this->stepExecution->getExecutionContext()->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $stepName = $this->stepExecution->getStepName();
        $jobInstance = $this->stepExecution->getJobExecution()->getJobInstance();
        $rawConfiguration = $jobInstance ? $jobInstance->getRawConfiguration() : [];

        return !empty($rawConfiguration[$stepName]) ? $rawConfiguration[$stepName] : $rawConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOption($name)
    {
        $configuration = $this->getConfiguration();

        return isset($configuration[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getOption($name, $default = null)
    {
        $configuration = $this->getConfiguration();
        if (isset($configuration[$name])) {
            return $configuration[$name];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function removeOption($name)
    {
        $configuration = $this->getConfiguration();
        if (isset($configuration[$name])) {
            unset($configuration[$name]);
            $this->stepExecution->getJobExecution()->getJobInstance()->setRawConfiguration($configuration);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchSize()
    {
        return $this->getOption('batch_size');
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchNumber()
    {
        return $this->getOption('batch_number');
    }
}
