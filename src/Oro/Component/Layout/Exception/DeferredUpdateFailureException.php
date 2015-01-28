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
     * @param string $message
     * @param array  $failedActions
     */
    public function __construct($message, array $failedActions)
    {
        $this->failedActions = $failedActions;
        parent::__construct(
            sprintf(
                '%s Actions: %s.',
                $message,
                implode(
                    ', ',
                    array_map(
                        function (array $action) {
                            return empty($action['args'])
                                ? sprintf('%s()', $action['name'])
                                : sprintf('%s(%s)', $action['name'], $action['args'][0]);
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
