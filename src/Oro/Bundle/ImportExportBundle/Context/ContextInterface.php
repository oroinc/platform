<?php

namespace Oro\Bundle\ImportExportBundle\Context;

interface ContextInterface
{
    /**
     * @param string $message
     */
    public function addError($message);

    /**
     * @param array $messages
     */
    public function addErrors(array $messages);

    /**
     * @return array
     */
    public function getErrors();

    /**
     * @return array
     */
    public function getFailureExceptions();

    /**
     * @param int $incrementBy
     */
    public function incrementReadCount($incrementBy = 1);

    /**
     * @return int
     */
    public function getReadCount();

    /**
     * @return void
     */
    public function incrementReadOffset();

    /**
     * @return int
     */
    public function getReadOffset();

    /**
     * @param int $incrementBy
     */
    public function incrementAddCount($incrementBy = 1);

    /**
     * @return int
     */
    public function getAddCount();

    /**
     * @param int $incrementBy
     */
    public function incrementUpdateCount($incrementBy = 1);

    /**
     * @return int
     */
    public function getUpdateCount();

    /**
     * @param int $incrementBy
     */
    public function incrementReplaceCount($incrementBy = 1);

    /**
     * @return int
     */
    public function getReplaceCount();

    /**
     * @param int $incrementBy
     */
    public function incrementDeleteCount($incrementBy = 1);

    /**
     * @return int
     */
    public function getDeleteCount();

    /**
     * @param int $incrementBy
     */
    public function incrementErrorEntriesCount($incrementBy = 1);

    /**
     * @return int
     */
    public function getErrorEntriesCount();

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setValue($name, $value);

    /**
     * @param string $name
     * @return mixed
     */
    public function getValue($name);

    /**
     * @return array
     */
    public function getConfiguration();

    /**
     * Has configuration option.
     *
     * @param string $name
     * @return mixed
     */
    public function hasOption($name);

    /**
     * Get configuration option.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getOption($name, $default = null);

    /**
     * Remove configuration option.
     *
     * @param mixed $name
     * @return mixed
     */
    public function removeOption($name);
}
