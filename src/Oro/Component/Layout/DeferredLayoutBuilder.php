<?php

namespace Oro\Component\Layout;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DeferredLayoutBuilder implements LayoutBuilderInterface, DeferredRawLayoutModifierInterface
{
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
     * @param LayoutBuilder $baseLayoutBuilder
     */
    public function __construct(LayoutBuilder $baseLayoutBuilder)
    {
        $this->builder = $baseLayoutBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function add($id, $parentId = null, $blockType = null, array $options = [])
    {
        $this->actions['add'][] = [$id, $parentId, $blockType, $options];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id)
    {
        $this->actions['remove'][] = [$id];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addAlias($alias, $id)
    {
        $this->actions['addAlias'][] = [$alias, $id];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAlias($alias)
    {
        $this->actions['removeAlias'][] = [$alias];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($id, $optionName, $optionValue)
    {
        $this->actions['setOption'][] = [$id, $optionName, $optionValue];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeOption($id, $optionName)
    {
        $this->actions['removeOption'][] = [$id, $optionName];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLayout($rootId = null)
    {
        $this->applyChanges();

        return $this->builder->getLayout($rootId);
    }

    /**
     * {@inheritdoc}
     */
    public function applyChanges()
    {
        $total = $this->calculateActionCount();
        while ($total) {
            $this->executeAll();
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
    protected function executeAll()
    {
        $this->executeAdd();
        $this->executeAddAlias();
        $this->executeSetOption();
        $this->executeRemoveOption();
        $this->executeRemove();
        $this->executeRemoveAlias();
    }

    /**
     * Executes all actions that add new items to the layout
     */
    protected function executeAdd()
    {
        $actionName = 'add';
        if (!empty($this->actions[$actionName])) {
            $function       = [$this->builder, $actionName];
            $skippedActions = [];
            foreach ($this->actions[$actionName] as $key => $action) {
                $parentId = $action[1];
                if (!empty($parentId) && !$this->builder->has($parentId)) {
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
    }

    /**
     * Executes all actions that remove items from the layout
     */
    protected function executeRemove()
    {
        $actionName = 'remove';
        if (!empty($this->actions[$actionName])) {
            $function       = [$this->builder, $actionName];
            $skippedActions = [];
            foreach ($this->actions[$actionName] as $key => $action) {
                $id = $action[0];
                if (!empty($id) && !$this->builder->has($id)) {
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
        // remove remaining 'remove' actions if there are no any 'add' actions
        if (!empty($this->actions[$actionName]) && empty($this->actions['add'])) {
            unset($this->actions[$actionName]);
        }
    }

    /**
     * Executes all actions that add aliases for layout items
     */
    protected function executeAddAlias()
    {
        $actionName = 'addAlias';
        if (!empty($this->actions[$actionName])) {
            $function       = [$this->builder, $actionName];
            $skippedActions = [];
            foreach ($this->actions[$actionName] as $key => $action) {
                $id = $action[1];
                if (!empty($id) && !$this->builder->has($id)) {
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
    }

    /**
     * Executes all actions that remove layout item aliases
     */
    protected function executeRemoveAlias()
    {
        $actionName = 'removeAlias';
        if (!empty($this->actions[$actionName])) {
            $function       = [$this->builder, $actionName];
            $skippedActions = [];
            foreach ($this->actions[$actionName] as $key => $action) {
                $alias = $action[0];
                if (!empty($alias) && !$this->builder->hasAlias($alias)) {
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
        // remove remaining 'removeAlias' actions if there are no any 'addAlias' actions
        if (!empty($this->actions[$actionName]) && empty($this->actions['addAlias'])) {
            unset($this->actions[$actionName]);
        }
    }

    /**
     * Executes all actions that change layout item options
     */
    protected function executeSetOption()
    {
        $actionName = 'setOption';
        if (!empty($this->actions[$actionName])) {
            $function       = [$this->builder, $actionName];
            $skippedActions = [];
            foreach ($this->actions[$actionName] as $key => $action) {
                $id = $action[0];
                if (!empty($id) && !$this->builder->has($id)) {
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
    }

    /**
     * Executes all actions that removes layout item options
     */
    protected function executeRemoveOption()
    {
        $actionName = 'removeOption';
        if (!empty($this->actions[$actionName])) {
            $function       = [$this->builder, $actionName];
            $skippedActions = [];
            foreach ($this->actions[$actionName] as $key => $action) {
                $id = $action[0];
                if (!empty($id) && !$this->builder->has($id)) {
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
