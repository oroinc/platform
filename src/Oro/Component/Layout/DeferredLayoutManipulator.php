<?php

namespace Oro\Component\Layout;

/**
 * Implements the layout manipulator which allows to perform manipulations in random order
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
     *      'add' => [ // add new items related actions: add, addAlias, setOption, removeOption
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
        $this->actions[self::GROUP_ADD][] = [__FUNCTION__, func_get_args()];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id)
    {
        $this->actions[self::GROUP_REMOVE][] = [__FUNCTION__, func_get_args()];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addAlias($alias, $id)
    {
        $this->actions[self::GROUP_ADD][] = [__FUNCTION__, func_get_args()];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAlias($alias)
    {
        $this->actions[self::GROUP_REMOVE][] = [__FUNCTION__, func_get_args()];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($id, $optionName, $optionValue)
    {
        $this->actions[self::GROUP_ADD][] = [__FUNCTION__, func_get_args()];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeOption($id, $optionName)
    {
        $this->actions[self::GROUP_ADD][] = [__FUNCTION__, func_get_args()];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function applyChanges()
    {
        $total = $this->calculateActionCount();
        if ($total !== 0) {
            $this->executeAllActions();
            // validate that all "execute*" methods have correct implementation
            // if all of them are implemented correctly the following exception will be never raised
            $applied = $total - $this->calculateActionCount();
            if ($applied === 0 && $applied !== $total) {
                throw new Exception\LogicException(
                    sprintf(
                        'Failed to apply scheduled changes. %d action(s) cannot be applied. Remained actions: %s.',
                        $total - $applied,
                        $this->getRemainedActionsBriefInfo()
                    )
                );
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
     * Executes all add new items related actions like
     *  * add
     *  * addAlias
     *  * setOption
     *  * removeOption
     */
    protected function executeAddActions()
    {
        if (!empty($this->actions[self::GROUP_ADD])) {
            $this->executeDependedActions(self::GROUP_ADD);
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
            // remove remaining 'remove' actions if there are no any 'add' actions
            if (!empty($this->actions[self::GROUP_REMOVE]) && empty($this->actions[self::GROUP_ADD])) {
                unset($this->actions[self::GROUP_REMOVE]);
            }
        }
    }

    /**
     * Checks whether an action is ready to execute
     *
     * @param string $name The action name
     * @param array  $args The action arguments
     *
     * @return bool
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
     * @return string
     */
    protected function getRemainedActionsBriefInfo()
    {
        $result = [];
        foreach ($this->actions as $actions) {
            foreach ($actions as $action) {
                if (empty($action[1])) {
                    $result[] = sprintf('%s()', $action[0]);
                } else {
                    $result[] = sprintf('%s(%s)', $action[0], $action[1][0]);
                }
            }
        }

        return implode(', ', $result);
    }
}
