<?php

namespace Oro\Bundle\ImportExportBundle\Context;

/**
 * Provides the ability to save and manage parameters
 */
class Context implements ContextInterface, BatchContextInterface
{
    const OPTION_FILE_PATH = 'filePath';
    const OPTION_DELIMITER = 'delimiter';
    const OPTION_ENCLOSURE = 'enclosure';
    const OPTION_ESCAPE = 'escape';
    const OPTION_FIRST_LINE_IS_HEADER = 'firstLineIsHeader';
    const OPTION_HEADER = 'header';
    const OPTION_BATCH_SIZE = 'batch_size';

    /**
     * @var array
     */
    private $configuration;

    /**
     * @var array
     */
    private $values = array();

    /**
     * @var array
     */
    private $failureExceptions = array();

    /**
     * @var array
     */
    private $errors = array();

    /**
     * @var array
     */
    private $postponedRows = array();

    /**
     * Constructor
     *
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function addError($message)
    {
        $this->errors[] = $message;
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
        return $this->errors;
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
     * {@inheritdoc}
     */
    public function getFailureExceptions()
    {
        return array_map(
            function ($e) {
                return $e['message'];
            },
            $this->failureExceptions
        );
    }

    /**
     * Add a failure exception
     * @param \Exception $e
     */
    public function addFailureException(\Exception $e)
    {
        $this->failureExceptions[] = array(
            'class'   => get_class($e),
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
            'trace'   => $e->getTraceAsString()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function incrementReadCount($incrementBy = 1)
    {
        $incrementedRead = $this->getOption('incremented_read', true);
        if ($incrementedRead) {
            $this->setValue('read_count', (int)$this->getValue('read_count') + $incrementBy);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getReadCount()
    {
        return $this->getValue('read_count');
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
     *
     * return $errors;
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
        $this->values[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($name)
    {
        return isset($this->values[$name])
            ? $this->values[$name]
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOption($name)
    {
        return isset($this->configuration[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getOption($name, $default = null)
    {
        if ($this->hasOption($name)) {
            return $this->configuration[$name];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function removeOption($name)
    {
        if ($this->hasOption($name)) {
            unset($this->configuration[$name]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchSize()
    {
        return $this->getValue(self::OPTION_BATCH_SIZE);
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchNumber()
    {
        return $this->getValue('batch_number');
    }
}
