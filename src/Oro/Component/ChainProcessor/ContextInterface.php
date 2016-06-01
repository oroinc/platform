<?php

namespace Oro\Component\ChainProcessor;

/**
 * Provides an interface of an execution context.
 */
interface ContextInterface extends \ArrayAccess
{
    /**
     * Checks whether an attribute exists in the context.
     *
     * @param string $key The name of an attribute
     *
     * @return bool
     */
    public function has($key);

    /**
     * Gets a value of an attribute from the context.
     *
     * @param string $key The name of an attribute
     *
     * @return mixed|null A value of an attribute or null if an attribute does not exist
     */
    public function get($key);

    /**
     * Adds or updates a value of an attribute in the context.
     *
     * @param string $key   The name of an attribute
     * @param mixed  $value The value of an attribute
     */
    public function set($key, $value);

    /**
     * Removes an attribute from the context.
     *
     * @param string $key The name of an attribute
     */
    public function remove($key);

    /**
     * Gets an identifier of processing action
     *
     * @return string
     */
    public function getAction();

    /**
     * Sets an identifier of processing action
     *
     * @param string $action
     */
    public function setAction($action);

    /**
     * Gets a group starting from which processors should be executed
     *
     * @return string|null
     */
    public function getFirstGroup();

    /**
     * Sets a group starting from which processors should be executed
     *
     * @param string $group
     */
    public function setFirstGroup($group);

    /**
     * Gets a group after which processors should not be executed
     *
     * @return string|null
     */
    public function getLastGroup();

    /**
     * Sets a group after which processors should not be executed
     *
     * @param string $group
     */
    public function setLastGroup($group);

    /**
     * Checks whether there is at least one group to be skipped
     *
     * @return bool
     */
    public function hasSkippedGroups();

    /**
     * Gets all groups to be skipped
     *
     * @return string[]
     */
    public function getSkippedGroups();

    /**
     * Adds the given group to a list of groups to be skipped
     *
     * @param string $group
     */
    public function skipGroup($group);

    /**
     * Removes the given group to a list of groups to be skipped
     *
     * @param string $group
     */
    public function undoGroupSkipping($group);

    /**
     * Checks whether result data exists
     *
     * @return bool
     */
    public function hasResult();

    /**
     * Gets result data
     *
     * @return mixed
     */
    public function getResult();

    /**
     * Sets result data
     *
     * @param mixed $data
     */
    public function setResult($data);

    /**
     * Removes result data
     */
    public function removeResult();
}
