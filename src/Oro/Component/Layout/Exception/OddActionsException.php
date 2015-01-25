<?php

namespace Oro\Component\Layout\Exception;

/**
 * Exception thrown if not all scheduled actions can be performed.
 */
class OddActionsException extends LogicException
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
    protected $actions;

    /**
     * @param string $message
     * @param array  $actions
     */
    public function __construct($message, array $actions)
    {
        $this->actions = $actions;
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
                        $actions
                    )
                )
            )
        );
    }

    /**
     * Returns the list of odd actions
     *
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }
}
