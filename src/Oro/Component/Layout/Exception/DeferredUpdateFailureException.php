<?php

namespace Oro\Component\Layout\Exception;

/**
 * Exception thrown if not all scheduled actions can be performed.
 */
class DeferredUpdateFailureException extends LogicException
{
    /**
     * @var array
     *
     * Example:
     *  [
     *      ['name' => 'add', 'args' => ['test', ...]],
     *      ['name' => 'remove', 'args' => ['test', ...]],
     *  ]
     */
    protected $failedActions;

    /**
     * @param string   $message            The failure reason
     * @param array    $failedActions      The list of failed action
     * @param callable $actionArgsToString The callback function to be used to convert an action arguments to a string
     *                                     function ($action) returns string
     */
    public function __construct($message, array $failedActions, $actionArgsToString = null)
    {
        $this->failedActions = $failedActions;
        parent::__construct(
            sprintf(
                '%s Actions: %s.',
                $message,
                implode(
                    ', ',
                    array_map(
                        function (array $action) use ($actionArgsToString) {
                            $args = is_callable($actionArgsToString)
                                ? call_user_func($actionArgsToString, $action)
                                : null;

                            return ($args !== null && empty($args)) || ($args === null && empty($action['args']))
                                ? sprintf('%s()', $action['name'])
                                : sprintf('%s(%s)', $action['name'], is_string($args) ? $args : $action['args'][0]);
                        },
                        $failedActions
                    )
                )
            )
        );
    }

    /**
     * Returns the list of failed actions
     *
     * @return array
     */
    public function getFailedActions()
    {
        return $this->failedActions;
    }
}
