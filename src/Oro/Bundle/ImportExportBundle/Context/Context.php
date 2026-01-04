<?php

namespace Oro\Bundle\ImportExportBundle\Context;

/**
 * Provides the ability to save and manage parameters
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Context implements ContextInterface, BatchContextInterface
{
    public const OPTION_FILE_PATH = 'filePath';
    public const OPTION_DELIMITER = 'delimiter';
    public const OPTION_ENCLOSURE = 'enclosure';
    public const OPTION_ESCAPE = 'escape';
    public const OPTION_FIRST_LINE_IS_HEADER = 'firstLineIsHeader';
    public const OPTION_HEADER = 'header';
    public const OPTION_BATCH_SIZE = 'batch_size';

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
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    #[\Override]
    public function addError($message)
    {
        $this->errors[] = $message;
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
        return $this->errors;
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

    #[\Override]
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

    #[\Override]
    public function incrementReadCount($incrementBy = 1)
    {
        $incrementedRead = $this->getOption('incremented_read', true);
        if ($incrementedRead) {
            $this->setValue('read_count', (int)$this->getValue('read_count') + $incrementBy);
        }
    }

    #[\Override]
    public function getReadCount()
    {
        return $this->getValue('read_count');
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
        $this->values[$name] = $value;
    }

    #[\Override]
    public function getValue($name)
    {
        return isset($this->values[$name])
            ? $this->values[$name]
            : null;
    }

    #[\Override]
    public function getConfiguration()
    {
        return $this->configuration;
    }

    #[\Override]
    public function hasOption($name)
    {
        return isset($this->configuration[$name]);
    }

    #[\Override]
    public function getOption($name, $default = null)
    {
        if ($this->hasOption($name)) {
            return $this->configuration[$name];
        }

        return $default;
    }

    #[\Override]
    public function removeOption($name)
    {
        if ($this->hasOption($name)) {
            unset($this->configuration[$name]);
        }
    }

    #[\Override]
    public function getBatchSize()
    {
        return $this->getValue(self::OPTION_BATCH_SIZE);
    }

    #[\Override]
    public function getBatchNumber()
    {
        return $this->getValue('batch_number');
    }
}
