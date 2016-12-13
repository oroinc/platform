<?php

namespace Oro\Component\Layout;

/**
 * The container which allows to collect modifications of an block option value.
 */
class OptionValueBag
{
    /** @var Action[] */
    protected $actions = [];

    /**
     * Adds new value
     *
     * @param mixed $value
     */
    public function add($value)
    {
        $this->actions[] = new Action('add', [$value]);
    }

    /**
     * Removes value
     *
     * @param mixed $value
     */
    public function remove($value)
    {
        $this->actions[] = new Action('remove', [$value]);
    }

    /**
     * Removes value
     *
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    public function replace($oldValue, $newValue)
    {
        $this->actions[] = new Action('replace', [$oldValue, $newValue]);
    }

    /**
     * Returns all actions
     *
     * @return Action[]
     */
    public function all()
    {
        return $this->actions;
    }

    /**
     * Builds a block option value using the given builder
     *
     * @return mixed The built value
     */
    public function buildValue()
    {
        $actions = [
            'add' => [],
            'replace' => [],
            'remove' => [],
        ];

        foreach ($this->actions as $action) {
            switch ($action->getName()) {
                case 'add':
                    $actions['add'][] = [$action->getArgument(0)];
                    break;
                case 'replace':
                    $actions['replace'][] = [$action->getArgument(0), $action->getArgument(1)];
                    break;
                case 'remove':
                    $actions['remove'][] = [$action->getArgument(0)];
                    break;
            }
        }

        $builder = $this->getBuilder();

        foreach ($actions as $action => $calls) {
            foreach ($calls as $arguments) {
                call_user_func_array([$builder, $action], $arguments);
            }
        }

        return $builder->get();
    }

    /**
     * Returns options builder based on values in value bag
     *
     * @return OptionValueBuilderInterface
     */
    protected function getBuilder()
    {
        $isArray = false;

        // guess builder type based on arguments
        if ($this->actions) {
            /** @var Action $action */
            $action = reset($this->actions);
            $arguments = $action->getArguments();
            if ($arguments) {
                $argument = reset($arguments);
                if (is_array($argument)) {
                    $isArray = true;
                }
            }
        }

        if ($isArray) {
            return new ArrayOptionValueBuilder();
        }

        return new StringOptionValueBuilder();
    }
}
