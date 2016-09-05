<?php

namespace Oro\Bundle\ImportExportBundle\Context;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Doctrine\Common\Collections\ArrayCollection;

class StepExecutionProxyContext implements ContextInterface
{
    /**
     * @var StepExecution
     */
    protected $stepExecution;

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
        $this->stepExecution->setReadCount(
            $this->stepExecution->getReadCount() + $incrementBy
        );
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
        $rawConfiguration = $this->stepExecution->getJobExecution()->getJobInstance()->getRawConfiguration();

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
        if ($this->hasOption($name)) {
            $configuration = $this->getConfiguration();
            return $configuration[$name];
        }
        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function removeOption($name)
    {
        if ($this->hasOption($name)) {
            $configuration = $this->getConfiguration();
            unset($configuration[$name]);
            $this->stepExecution->getJobExecution()->getJobInstance()->setRawConfiguration($configuration);
        }
    }
}
