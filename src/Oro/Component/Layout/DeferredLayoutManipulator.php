<?php

namespace Oro\Component\Layout;

/**
 * Implements the layout manipulator which allows to perform manipulations in random order
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DeferredLayoutManipulator implements DeferredRawLayoutManipulatorInterface
{
    /** The group name for add new items related actions */
    const GROUP_ADD = 'add';

    /** The group name for remove items related actions */
    const GROUP_REMOVE = 'remove';

    /** The action name for add layout item */
    const ADD = 'add';

    /** The action name for remove layout item */
    const REMOVE = 'remove';

    /** The action name for move layout item */
    const MOVE = 'move';

    /** The action name for add the alias for the layout item */
    const ADD_ALIAS = 'addAlias';

    /** The action name for remove the alias for the layout item */
    const REMOVE_ALIAS = 'removeAlias';

    /** The action name for add/update an option for the layout item */
    const SET_OPTION = 'setOption';

    /** The action name for remove an option for the layout item */
    const REMOVE_OPTION = 'removeOption';

    /** @var RawLayoutAccessorInterface */
    protected $layout;

    /**
     * The list of all scheduled actions to be executed by applyChanges method
     *
     * @var array
     *
     * Example:
     *  [
     *      'add' => [ // add new items related actions: add, move, addAlias, setOption, removeOption
     *          ['add', ['root', null, 'root', []]],
     *          ['add', ['my_label', 'my_root', 'label', ['text' => 'test']]],
     *          ['addAlias', ['my_root', 'root']],
     *      ],
     *      'remove' => [ // remove items related actions: remove, removeAlias
     *          ['remove', ['my_label']],
     *          ['removeAlias', ['my_root']],
     *      ],
     *  ]
     */
    protected $actions = [];

    /**
     * @param RawLayoutAccessorInterface $layout
     */
    public function __construct(RawLayoutAccessorInterface $layout)
    {
        $this->layout = $layout;
    }

    /**
     * {@inheritdoc}
     */
    public function add($id, $parentId = null, $blockType = null, array $options = [])
    {
        $this->actions[self::GROUP_ADD][] = [__FUNCTION__, [$id, $parentId, $blockType, $options]];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id)
    {
        $this->actions[self::GROUP_REMOVE][] = [__FUNCTION__, [$id]];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function move($id, $parentId = null, $siblingId = null, $prepend = false)
    {
        $this->actions[self::GROUP_ADD][] = [__FUNCTION__, [$id, $parentId, $siblingId, $prepend]];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addAlias($alias, $id)
    {
        $this->actions[self::GROUP_ADD][] = [__FUNCTION__, [$alias, $id]];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAlias($alias)
    {
        $this->actions[self::GROUP_REMOVE][] = [__FUNCTION__, [$alias]];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($id, $optionName, $optionValue)
    {
        $this->actions[self::GROUP_ADD][] = [__FUNCTION__, [$id, $optionName, $optionValue]];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeOption($id, $optionName)
    {
        $this->actions[self::GROUP_ADD][] = [__FUNCTION__, [$id, $optionName]];

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception\DeferredUpdateFailureException if not all scheduled action have been performed
     */
    public function applyChanges()
    {
        $total = $this->calculateActionCount();
        if ($total !== 0) {
            $this->executeAllActions();
            $this->checkRemainingActions();
            // check that all scheduled actions have been performed
            $applied = $total - $this->calculateActionCount();
            if ($applied === 0 && $applied !== $total) {
                throw $this->createFailureException();
            }
        }
    }

    /**
     * Returns the total number of actions in all groups
     *
     * @return int
     */
    protected function calculateActionCount()
    {
        $counter = 0;
        foreach ($this->actions as $actions) {
            $counter += count($actions);
        }

        return $counter;
    }

    /**
     * Executes actions from all groups
     */
    protected function executeAllActions()
    {
        $this->executeAddActions();
        $this->executeRemoveActions();
    }

    /**
     * Checks if there are any not executed actions and remove actions which are not important
     */
    protected function checkRemainingActions()
    {
        // remove remaining 'move' actions
        if (!empty($this->actions[self::GROUP_ADD])) {
            foreach ($this->actions[self::GROUP_ADD] as $key => $action) {
                if ($action[0] === self::MOVE) {
                    unset($this->actions[self::GROUP_ADD][$key]);
                }
            }
        }
        // remove remaining 'remove' actions if there are no any 'add' actions
        if (!empty($this->actions[self::GROUP_REMOVE])) {
            if (!empty($this->actions[self::GROUP_REMOVE]) && empty($this->actions[self::GROUP_ADD])) {
                unset($this->actions[self::GROUP_REMOVE]);
            }
        }
    }

    /**
     * Executes all add new items related actions like
     *  * add
     *  * move
     *  * addAlias
     *  * setOption
     *  * removeOption
     */
    protected function executeAddActions()
    {
        if (!empty($this->actions[self::GROUP_ADD])) {
            $this->executeDependedActions(self::GROUP_ADD);
        }
        // the siblingId argument in the 'move' action is "optional", this means that if it is not possible
        // to locate an item near to sibling due to the sibling item does not exist
        // we should try to execute such 'move' action without siblingId argument
        if (!empty($this->actions[self::GROUP_ADD])) {
            $hasChanges = false;
            foreach ($this->actions[self::GROUP_ADD] as $key => $action) {
                if ($action[0] === self::MOVE && !empty($action[1][2])) {
                    if (!empty($action[1][2])) {
                        $this->actions[self::GROUP_ADD][$key][1][2] = null;
                        $hasChanges                                 = true;
                    }
                }
            }
            if ($hasChanges) {
                $this->executeActions(self::GROUP_ADD);
            }
        }
    }

    /**
     * Executes all remove items related actions like
     *  * remove
     *  * removeAlias
     */
    protected function executeRemoveActions()
    {
        if (!empty($this->actions[self::GROUP_REMOVE])) {
            $this->executeActions(self::GROUP_REMOVE);
        }
    }

    /**
     * Checks whether an action is ready to execute
     *
     * @param string $name The action name
     * @param array  $args The action arguments
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function isActionReadyToExecute($name, $args)
    {
        switch ($name) {
            case self::ADD:
                $parentId = $args[1];

                return empty($parentId) || $this->layout->has($parentId);
            case self::REMOVE:
            case self::SET_OPTION:
            case self::REMOVE_OPTION:
                $id = $args[0];

                return empty($id) || $this->layout->has($id);
            case self::MOVE:
                $id        = $args[0];
                $parentId  = $args[1];
                $siblingId = $args[2];

                return
                    (empty($id) || $this->layout->has($id))
                    && (empty($parentId) || $this->layout->has($parentId))
                    && (empty($siblingId) || $this->layout->has($siblingId));
            case self::ADD_ALIAS:
                $id = $args[1];

                return empty($id) || $this->layout->has($id);
            case self::REMOVE_ALIAS:
                $alias = $args[0];

                return empty($alias) || $this->layout->hasAlias($alias);
        }

        return true;
    }

    /**
     * Executes actions from the given group
     * Use this method if the group does not contain depended each other actions
     *
     * @param string $group
     */
    protected function executeActions($group)
    {
        foreach ($this->actions[$group] as $key => $action) {
            if ($this->isActionReadyToExecute($action[0], $action[1])) {
                call_user_func_array([$this->layout, $action[0]], $action[1]);
                unset($this->actions[$group][$key]);
            }
        }
    }

    /**
     * Executes depended actions from the given group
     * Use this method if the group can contain depended each other actions
     * This method guarantee that all actions are executed in the order they are registered
     *
     * @param string $group
     */
    protected function executeDependedActions($group)
    {
        $continue = true;
        while ($continue) {
            $continue    = false;
            $hasExecuted = false;
            $hasSkipped  = false;
            foreach ($this->actions[$group] as $key => $action) {
                if ($this->isActionReadyToExecute($action[0], $action[1])) {
                    call_user_func_array([$this->layout, $action[0]], $action[1]);
                    unset($this->actions[$group][$key]);
                    $hasExecuted = true;
                    if ($hasSkipped) {
                        // start execution from the begin
                        $continue = true;
                        break;
                    }
                } else {
                    $hasSkipped = true;
                    if ($hasExecuted) {
                        // start execution from the begin
                        $continue = true;
                        break;
                    }
                }
            }
        }
    }

    /**
     * @return Exception\DeferredUpdateFailureException
     */
    protected function createFailureException()
    {
        $exActions = [];
        foreach ($this->actions as $actions) {
            foreach ($actions as $action) {
                $exActions[] = ['name' => $action[0], 'args' => isset($action[1]) ? $action[1] : []];
            }
        }

        return new Exception\DeferredUpdateFailureException(
            sprintf(
                'Failed to apply scheduled changes. %d action(s) cannot be applied.',
                count($exActions)
            ),
            $exActions
        );
    }
}
