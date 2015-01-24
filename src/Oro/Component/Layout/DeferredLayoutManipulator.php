<?php

namespace Oro\Component\Layout;

class DeferredLayoutManipulator implements DeferredRawLayoutManipulatorInterface, LayoutManipulatorInterface
{
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

    /** @var LayoutBuilder */
    protected $builder;

    /**
     * The list of all scheduled actions to be executed by applyChanges method
     *
     * @var array
     *
     * Example:
     *  [
     *      'add' => [
     *          ['root', null, 'root', []],
     *          ['my_label', 'root', 'label', ['text' => 'test']]
     *      ],
     *      'remove' => [
     *          ['my_label']
     *      ],
     *  ]
     */
    protected $actions = [];

    /**
     * @param LayoutBuilder $layoutBuilder
     */
    public function __construct(LayoutBuilder $layoutBuilder)
    {
        $this->builder = $layoutBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function add($id, $parentId = null, $blockType = null, array $options = [])
    {
        $this->actions[self::ADD][] = [$id, $parentId, $blockType, $options];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id)
    {
        $this->actions[self::REMOVE][] = [$id];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addAlias($alias, $id)
    {
        $this->actions[self::ADD_ALIAS][] = [$alias, $id];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAlias($alias)
    {
        $this->actions[self::REMOVE_ALIAS][] = [$alias];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($id, $optionName, $optionValue)
    {
        $this->actions[self::SET_OPTION][] = [$id, $optionName, $optionValue];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeOption($id, $optionName)
    {
        $this->actions[self::REMOVE_OPTION][] = [$id, $optionName];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function applyChanges()
    {
        $total = $this->calculateActionCount();
        // @todo: check if this while is required before close https://magecore.atlassian.net/browse/BAP-7148
        while ($total !== 0) {
            $this->executeAllActions();
            $remained = $this->calculateActionCount();

            // validate that all "execute*" methods have correct implementation
            // if all of them are implemented correctly the following exception will be never raised
            $applied = $total - $remained;
            if ($applied === 0 && $applied !== $total) {
                throw new Exception\LogicException(
                    sprintf(
                        'Failed to apply scheduled changes. %d action(s) cannot be applied. Remained actions: %s.',
                        $total - $applied,
                        $this->getRemainedActionsBriefInfo()
                    )
                );
            }

            // prepare for the next loop
            $total = $remained;
        }
    }

    /**
     * Returns the total number of actions
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
     * Executes all actions
     */
    protected function executeAllActions()
    {
        $this->applyAddChanges();
        $this->executeRemoveActions();
        $this->executeRemoveAliasActions();
    }

    /**
     * Applies the following actions:
     *  - add new items to the layout
     *  - add aliases for layout items
     *  - modify the layout item options
     */
    protected function applyAddChanges()
    {
        $applied = -1;
        $total   = $this->calculateActionCount();
        while ($total !== 0 && $applied !== 0) {
            $this->executeAddActions();
            $this->executeAddAliasActions();
            $this->executeSetOptionActions();
            $this->executeRemoveOptionActions();

            // prepare for the next loop
            $remained = $this->calculateActionCount();
            $applied  = $total - $remained;
            $total    = $remained;
        }
    }

    /**
     * Executes all actions that add new items to the layout
     */
    protected function executeAddActions()
    {
        $this->executeActions(
            self::ADD,
            function (array $action) {
                $parentId = $action[1];

                return empty($parentId) || $this->builder->has($parentId);
            }
        );
    }

    /**
     * Executes all actions that remove items from the layout
     */
    protected function executeRemoveActions()
    {
        $this->executeActions(
            self::REMOVE,
            function (array $action) {
                $id = $action[0];

                return empty($id) || $this->builder->has($id);
            }
        );
        // remove remaining 'remove' actions if there are no any 'add' actions
        if (!empty($this->actions[self::REMOVE]) && empty($this->actions[self::ADD])) {
            unset($this->actions[self::REMOVE]);
        }
    }

    /**
     * Executes all actions that add aliases for layout items
     */
    protected function executeAddAliasActions()
    {
        $this->executeActions(
            self::ADD_ALIAS,
            function (array $action) {
                $id = $action[1];

                return empty($id) || $this->builder->has($id);
            }
        );
    }

    /**
     * Executes all actions that remove layout item aliases
     */
    protected function executeRemoveAliasActions()
    {
        $this->executeActions(
            self::REMOVE_ALIAS,
            function (array $action) {
                $alias = $action[0];

                return empty($alias) || $this->builder->hasAlias($alias);
            }
        );
        // remove remaining 'removeAlias' actions if there are no any 'addAlias' actions
        if (!empty($this->actions[self::REMOVE_ALIAS]) && empty($this->actions[self::ADD_ALIAS])) {
            unset($this->actions[self::REMOVE_ALIAS]);
        }
    }

    /**
     * Executes all actions that change layout item options
     */
    protected function executeSetOptionActions()
    {
        $this->executeActions(
            self::SET_OPTION,
            function (array $action) {
                $id = $action[0];

                return empty($id) || $this->builder->has($id);
            }
        );
    }

    /**
     * Executes all actions that removes layout item options
     */
    protected function executeRemoveOptionActions()
    {
        $this->executeActions(
            self::REMOVE_OPTION,
            function (array $action) {
                $id = $action[0];

                return empty($id) || $this->builder->has($id);
            }
        );
    }

    /**
     * @param string   $actionName       The action name
     * @param \Closure $isReadyToExecute The callback is used for check if an action is ready to execute
     *                                   function (array $action) return boolean
     */
    protected function executeActions($actionName, \Closure $isReadyToExecute)
    {
        if (empty($this->actions[$actionName])) {
            return;
        }

        $function       = [$this->builder, $actionName];
        $skippedActions = [];
        foreach ($this->actions[$actionName] as $key => $action) {
            if (!$isReadyToExecute($action)) {
                $skippedActions[] = $action;
                continue;
            }
            call_user_func_array($function, $action);
        }
        if (!empty($skippedActions)) {
            $this->actions[$actionName] = $skippedActions;
        } else {
            unset($this->actions[$actionName]);
        }
    }

    /**
     * @return string
     */
    protected function getRemainedActionsBriefInfo()
    {
        $result = [];
        foreach ($this->actions as $actionName => $actions) {
            foreach ($actions as $action) {
                if (empty($action)) {
                    $result[] = sprintf('%s()', $actionName);
                } else {
                    $result[] = sprintf('%s(%s)', $actionName, $action[0]);
                }
            }
        }

        return implode(', ', $result);
    }
}
