<?php


namespace Oro\Bundle\ImportExportBundle\Context;

class Context implements ContextInterface
{
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
    public function incrementReadCount()
    {
        $this->setValue('read_count', (int)$this->getValue('read_count') + 1);
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
    public function incrementAddCount()
    {
        $this->setValue('add_count', (int)$this->getValue('add_count') + 1);
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
    public function incrementUpdateCount()
    {
        $this->setValue('update_count', (int)$this->getValue('update_count') + 1);
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
    public function incrementReplaceCount()
    {
        $this->setValue('replace_count', (int)$this->getValue('replace_count') + 1);
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
    public function incrementDeleteCount()
    {
        $this->setValue('delete_count', (int)$this->getValue('delete_count') + 1);
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
    public function incrementErrorEntriesCount()
    {
        $this->setValue('error_entries_count', (int)$this->getValue('error_entries_count') + 1);
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
}
